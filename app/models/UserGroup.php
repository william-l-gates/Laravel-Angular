<?php
/**
 * Created by PhpStorm.
 * User: fulfilldirect
 * Date: 9/6/2015
 * Time: 6:20 PM
 */

class Usergroup extends Eloquent{

    /**
     * The database table used by the model.
     *
     * @var string
     */

    // Table Name to be used
    protected $table = 'user_groups';


    // Overwriting Default Audits Fields with the Fields in Table
    const CREATED_AT = 'CREATE_TS';
    const UPDATED_AT  = 'MODIFY_TS';

    protected $primaryKey = 'id';

    public function usergroupchannelrights()
    {
       return $this->hasMany('Usergroupchannelright','UserGroup_Id','UserGroup_Id');
    }


}