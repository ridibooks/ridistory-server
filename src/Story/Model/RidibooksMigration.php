<?php
namespace Story\Model;

class RidibooksMigration
{
    public static function add($u_id, $ridibooks_id)
    {
        $bind = array(
            'u_id' => $u_id,
            'ridibooks_id' => $ridibooks_id
        );
        global $app;
        $app['db']->insert('ridibooks_migration_history', $bind);
        return $app['db']->lastInsertId();
    }

    public static function isMigrated($u_id)
    {
        $sql = <<<EOT
SELECT COUNT(*) FROM ridibooks_migration_history
WHERE u_id = ?
EOT;
        global $app;
        $r = $app['db']->fetchColumn($sql, array($u_id));
        return ($r > 0) ? true : false;
    }

    public static function getMigratedCount()
    {
        $sql = <<<EOT
SELECT COUNT(*) FROM ridibooks_migration_history
EOT;
        global $app;
        return $app['db']->fetchColumn($sql);
    }

    public static function getListByOffsetAndSize($offset, $size)
    {
        $sql = <<<EOT
SELECT * FROM ridibooks_migration_history
ORDER BY migration_time DESC LIMIT ?, ?
EOT;
        global $app;
        $stmt = $app['db']->executeQuery($sql,
            array($offset, $size),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT)
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getListBySearchTypeAndKeyword($search_type, $search_keyword)
    {
        $sql = <<<EOT
SELECT * FROM ridibooks_migration_history
EOT;
        if ($search_type == 'uid') {
            $sql .= ' WHERE u_id = ? ORDER BY migration_time DESC';
            $bind = array($search_keyword);
        } else if ($search_type == 'ridibooks_id') {
            $sql .= ' WHERE ridibooks_id like ? ORDER BY migration_time DESC';
            $bind = array('%' . $search_keyword . '%');
        } else {
            $sql = null;
            $bind = null;
        }

        global $app;
        return $app['db']->fetchAll($sql, $bind);
    }

    public static function update($u_id, $values)
    {
        global $app;
        return $app['db']->update('ridibooks_migration_history', $values, array('u_id' => $u_id));
    }

    public static function delete($u_id)
    {
        global $app;
        return $app['db']->delete('ridibooks_migration_history', array('_id' => $u_id));
    }
}