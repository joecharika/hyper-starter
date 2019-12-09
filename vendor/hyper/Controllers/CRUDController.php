<?php

namespace Hyper\Controllers;


use Hyper\Application\Request;
use Hyper\Functions\Str;
use Hyper\Notifications\{HttpMessage, HttpMessageType};
use Hyper\QueryBuilder\Query;

/**
 * Class CRUDController
 * @package hyper\Application
 * @uses \hyper\Application\BaseController
 */
class CRUDController extends BaseController
{
    #region CREATE: Create/Add/Insert

    /**
     * @param null $model
     * @return string
     */
    public function create($model = null)
    {
        return self::view("$this->name.create", $model);
    }

    /**
     *
     */
    public function postCreate()
    {
        $this->db->add(Request::bind(new $this->model()));
        Request::redirectTo('index', "$this->name", null, 'Successfully added');
    }

    #endregion

    #region READ: Read/Details/View

    /**
     * @return string
     */
    public function read()
    {
        return self::view("$this->name.read", Request::fromParam());
    }

    #endregion

    #region UPDATE: Edit/Put/Patch

    /**
     * @param null $model
     * @return string
     */
    public function edit($model = null)
    {
        return self::view("{$this->name}.edit", $model ?? Request::fromParam());
    }

    /**
     *
     */
    public function postEdit()
    {
        if ($this->db->update(Request::bind(new $this->model())))
            Request::redirectTo('index', $this->name, null, 'Successfully updated');
        else
            Request::redirectTo('edit', "$this->name", Request::fromParam()->id, 'Failed to update');
    }

    #endregion

    #region DELETE: Remove/Delete/Extinguish

    /**
     *
     */
    public function delete()
    {
        self::view("$this->name.delete", Request::fromParam());
    }

    /**
     *
     */
    public function postDelete()
    {
        if ($this->db->delete(Request::fromParam()) === 1)
            Request::redirectTo("index", "$this->name", null, 'Successfully deleted');
        else
            Request::redirectTo("delete", "$this->name", Request::fromParam()->id, 'Failed to delete');
    }

    /**
     *
     */
    public function deleteAll()
    {
        return self::view('shared.delete', $this->db->all()->toList());
    }

    /**
     * Delete everything
     */
    public function postDeleteAll()
    {
        if ($this->db->delete(null, true, true))
            Request::redirectTo('index', "$this->name", null, 'Successfully deleted everything');
        else
            Request::redirectTo('delete', "$this->name", Request::fromParam()->id, 'Failed to delete');
    }

    #endregion

    #region PERK: Recycle/Restore
    /**
     *
     */
    public function recycle()
    {
        $recycleBin = $this->db->recycleBin();

        if (empty($recycleBin))
            Request::redirectToUrl(Request::previousUrl(),
                new HttpMessage('Nothing to restore here', HttpMessageType::WARNING));

        return self::view('shared.recycle', $recycleBin);
    }

    /**
     * Restore object
     */
    public function postRecycle()
    {
        $entity = (new Query)
            ->selectFrom(Str::pluralize(Request::$route->realController))
            ->where('id', Request::data()->id)
            ->exec($this->model)
            ->getResult();

        $recycle = $this->db->recycle($entity);

        if ($recycle)
            Request::redirectTo('index', $this->name, null, 'Restored item');
        else
            Request::redirectTo('delete', $this->name, Request::fromParam()->id, 'Failed to restore');
    }
    #endregion
}
