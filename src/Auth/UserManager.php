<?php
namespace App\Auth;

class UserManager
{
    private static $usersFile = null;

    private static function getUsersFile()
    {
        if (self::$usersFile === null) {
            self::$usersFile = STORAGE_DIR . '/.users.json';
        }
        return self::$usersFile;
    }

    public static function load()
    {
        $file = self::getUsersFile();
        if (!file_exists($file)) {
            return self::migrate();
        }
        $data = file_get_contents($file);
        $users = json_decode($data, true);
        return is_array($users) ? $users : [];
    }

    public static function save($users)
    {
        file_put_contents(self::getUsersFile(), json_encode($users, JSON_PRETTY_PRINT), LOCK_EX);
    }

    public static function migrate()
    {
        $username = trim((string) getenv('BOOTSTRAP_ADMIN_USER'));
        $password = (string) getenv('BOOTSTRAP_ADMIN_PASSWORD');
        $defaults = [];
        if ($username !== '' && $password !== '') {
            $defaults[] = ['user' => $username, 'pass' => password_hash($password, PASSWORD_DEFAULT), 'role' => 'admin'];
        }
        self::save($defaults);
        return $defaults;
    }

    public static function create($username, $password, $role)
    {
        $users = self::load();
        foreach ($users as $u) {
            if ($u['user'] === $username) return false;
        }
        $users[] = ['user' => $username, 'pass' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role];
        self::save($users);
        return true;
    }

    public static function update($oldUsername, $newUsername, $password, $role)
    {
        $users = self::load();
        foreach ($users as &$u) {
            if ($u['user'] === $oldUsername) {
                $u['user'] = $newUsername;
                if (!empty($password)) {
                    $u['pass'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $u['role'] = $role;
                self::save($users);
                return true;
            }
        }
        return false;
    }

    public static function delete($username)
    {
        $users = self::load();
        $adminCount = 0;
        foreach ($users as $u) {
            if ($u['role'] === 'admin') $adminCount++;
        }
        if ($adminCount <= 1) {
            foreach ($users as $u) {
                if ($u['user'] === $username && $u['role'] === 'admin') return false;
            }
        }
        $filtered = array_values(array_filter($users, function ($u) use ($username) {
            return $u['user'] !== $username;
        }));
        if (count($filtered) === count($users)) return false;
        self::save($filtered);
        return true;
    }
}
