<?php


namespace Naran\Axis\Model;


use WP_User_Query;

abstract class RolesCapsModel extends Model implements ActivationDeactivation
{
    protected $caps = [];

    protected $capsCollected = false;

    /**
     * Role name.
     *
     * @return string
     */
    abstract public static function getRoleName();

    /**
     * Role display name.
     *
     * @return string
     */
    abstract public static function getDisplayName();

    /**
     * Alias of getRoleName()
     *
     * @return string
     */
    public static function Rn()
    {
        return static::getRoleName();
    }

    /**
     * Alias of getDisplayName()
     *
     * @return string
     */
    public static function Dn()
    {
        return static::getDisplayName();
    }

    public static function getRole()
    {
        return get_role(static::getRoleName());
    }

    public function getDefinedCaps()
    {
        if ( ! $this->capsCollected) {
            foreach (get_class_methods($this) as $method) {
                if (strlen($method) > 6 && substr($method, 0, 6) === 'getCap') {
                    $returned = call_user_func([$this, $method]);
                    if (is_string($returned) && ! empty($returned)) {
                        $this->caps[$returned] = true;
                    }
                }
            }
            ksort($this->caps);
            $this->capsCollected = true;
        }

        return $this->caps;
    }

    public function addRole()
    {
        $role = static::getRole();

        if ( ! $role) {
            $role = add_role(static::Rn(), static::Dn(), $this->getDefinedCaps());
            if ( ! $role) {
                wp_die(__('add_role() returned null.', 'naran-axis'));
            }
        }

        return $role;
    }

    public function removeRole()
    {
        remove_role(static::Rn());
    }

    public static function assignTo($vars)
    {
        $query = new WP_User_Query($vars);

        /** @var \WP_User $user */
        foreach ($query->get_results() as $user) {
            $user->add_role(static::getRoleName());
        }
    }

    public function revokeFrom($vars)
    {
        $query = new WP_User_Query($vars);

        /** @var \WP_User $user */
        foreach ($query->get_results() as $user) {
            $user->remove_role(static::getRoleName());
        }
    }

    /**
     * Assign role to users who have one of returned roles. when the plugin is activated.
     * Remove this role when the plugin is deactivated.
     *
     * @return string[]|array<array> Role name, or array of WP_User_Query query vars array.
     */
    public function rolesToAppend()
    {
        return ['administrator'];
    }

    public function activationSetup()
    {
        $this->addRole();

        $roles = $this->rolesToAppend();
        foreach ($roles as $role) {
            if (is_string($role)) {
                $this->assignTo(['role' => $role]);
            } elseif (is_array($role)) {
                $this->assignTo($role);
            }
        }
    }

    public function deactivationCleanup()
    {
        $roles = $this->rolesToAppend();
        foreach ($roles as $role) {
            if (is_string($role)) {
                $this->revokeFrom(['role' => $role]);
            } else {
                $this->revokeFrom($role);
            }
        }

        $this->removeRole();
    }
}
