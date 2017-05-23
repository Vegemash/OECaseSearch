<?php

class CaseSearchController extends BaseModuleController
{
    public function filters()
    {
        return array(
            'accessControl',
            //'ajaxOnly + addParameter',
            'postOnly + delete',
        );
    }

    public function accessRules()
    {
        return array(
            array(
                'allow',
                'actions' => array('index', 'addParameter'),
                'users' => array('admin'),
            ),
        );
    }

    public function actionIndex()
    {
        $valid = true;
        $parameters = array();
        $patients = array();
        foreach ($this->getModule()->parameters as $parameter) {
            $paramName = $parameter . 'Parameter';
            if (isset($_POST[$paramName])) {
                foreach ($_POST[$paramName] as $id => $param) {
                    $newParam = new $paramName;
                    $newParam->attributes = $param;
                    if (!$newParam->validate()) {
                        $valid = false;
                    }
                    $parameters[$id] = $newParam;
                }
            }
        }
        if (!empty($parameters) and $valid) {
            $dataProvider = $this->module->getSearchProvider();
            $results = $dataProvider->search($parameters);
            // deconstruct the results list into a single array of primary keys.
            $ids = array();
            foreach ($results as $result)
                $ids[] = $result['id'];
            $patients = Patient::model()->findAllByPk($ids);
        }
        $paramList = $this->module->getParamList();
        $this->render('index', array(
            'paramList' => $paramList,
            'params' => $parameters,
            'patients' => $patients
        ));
    }

    public function actionAddParameter()
    {
        $id = $_GET['id'];
        $param = $_GET['param'];
        $parameter = new $param;

        $this->renderPartial('parameter_form', array(
            'model' => $parameter,
            'id' => $id,
        ));
    }
}