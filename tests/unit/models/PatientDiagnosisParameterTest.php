<?php

/**
 * Class PatientDiagnosisParameterTest
 */
class PatientDiagnosisParameterTest extends CDbTestCase
{
    protected $object;

    /**
     * @var DBProvider
     */
    protected $searchProvider;
    protected $fixtures = array(
        'disorder' => 'Disorder',
        'secondary_diagnosis' => 'SecondaryDiagnosis',
        'patient' => 'Patient'
    );

    protected function setUp()
    {
        parent::setUp();
        $this->object = new PatientDiagnosisParameter();
        $this->searchProvider = new DBProvider('mysql');
        $this->object->id = 0;
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->object, $this->searchProvider);
    }

    /**
     * @covers PatientDiagnosisParameter::query()
     */
    public function testQuery()
    {
        $this->object->textValue = 5;

        $correctOps = array(
            'LIKE',
            'NOT LIKE',
        );
        $confirmedStates = array(
            '',
            '0',
            '1'
        );
        $invalidOps = array(
            '=',
        );

        // Ensure the query is correct for each operator.
        foreach ($correctOps as $operator) {
            $this->object->operation = $operator;
            foreach ($confirmedStates as $state)
            {
                $this->object->isConfirmed = $state;
                $sqlValue = 'SELECT p.id 
FROM patient p 
LEFT JOIN secondary_diagnosis sd 
  ON sd.patient_id = p.id 
LEFT JOIN disorder d 
  ON d.id = sd.disorder_id 
WHERE LOWER(d.term) LIKE LOWER(:p_d_value_0)';
                if ($state === '0')
                {
                    $sqlValue .= ' AND sd.is_confirmed = :p_d_confirmed_0';
                }
                elseif ($state === '1')
                {
                    $sqlValue .= ' AND ((:p_d_confirmed_0 = ' . PatientDiagnosisParameter::DIAGNOSIS_CONFIRMED . ' AND sd.is_confirmed IS NULL) OR sd.is_confirmed = :p_d_confirmed_0)';
                }

                if ($operator === 'NOT LIKE')
                {
                    $sqlValue = "SELECT p1.id 
FROM patient p1
WHERE p1.id NOT IN (
  $sqlValue
)";
                }
                $this->assertEquals($sqlValue, $this->object->query($this->searchProvider));
            }
        }

        // Ensure that a HTTP exception is raised if an invalid operation is specified.
        $this->setExpectedException(CHttpException::class);
        foreach ($invalidOps as $operator) {
            $this->object->operation = $operator;
            $this->object->query($this->searchProvider);
        }
    }

    /**
     * @covers PatientDiagnosisParameter::bindValues()
     */
    public function testBindValues()
    {
        $this->object->textValue = 'Diabetes';
        $this->object->isConfirmed = '1';
        $expected = array(
            'p_d_value_0' => '%' . $this->object->textValue . '%',
            'p_d_confirmed_0' => $this->object->isConfirmed
        );

        // Ensure that all bind values are returned.
        $this->assertEquals($expected, $this->object->bindValues());

        $this->object->isConfirmed = '';

        $expected = array(
            'p_d_value_0' => '%' . $this->object->textValue . '%',
        );

        $this->assertEquals($expected, $this->object->bindValues());
    }

    /**
     * @covers DBProvider::search()
     * @covers PatientDiagnosisParameter::query()
     */
    public function testSearchLike()
    {
        $expected = array($this->patient('patient1'), $this->patient('patient2'));

        $this->object->operation = 'LIKE';
        $this->object->textValue = 'Essential hypertension';
        $this->object->isConfirmed = '';

        $results = $this->searchProvider->search(array($this->object));

        $ids = array();
        foreach ($results as $result)
        {
            $ids[] = $result['id'];
        }

        $patients = Patient::model()->findAllByPk($ids);

        $this->assertEquals($expected, $patients);

        $expected = array($this->patient('patient1'));
        $this->object->isConfirmed = '1';

        $results = $this->searchProvider->search(array($this->object));

        $ids = array();
        foreach ($results as $result)
        {
            $ids[] = $result['id'];
        }
        $patients = Patient::model()->findAllByPk($ids);

        $this->assertEquals($expected, $patients);

        // Test unconfirmed diagnoses only.
        $this->object->textValue = 'Diabetes mellitus type 1';
        $this->object->isConfirmed = '0';

        $results = $this->searchProvider->search(array($this->object));

        $ids = array();
        foreach ($results as $result)
        {
            $ids[] = $result['id'];
        }
        $patients = Patient::model()->findAllByPk($ids);

        $this->assertEquals($expected, $patients);
    }

    /*public function testSearchNotLike()
    {
        $expected = array($this->patient('patient1'), $this->patient('patient2'));

        $this->object->operation = 'NOT LIKE';
        $this->object->textValue = 'Essential hypertension';
        $this->object->isConfirmed = '';

        $results = $this->searchProvider->search(array($this->object));

        $ids = array();
        foreach ($results as $result)
        {
            $ids[] = $result['id'];
        }

        $patients = Patient::model()->findAllByPk($ids);

        $this->assertEquals($expected, $patients);

        $expected = array($this->patient('patient1'));
        $this->object->isConfirmed = '1';

        $results = $this->searchProvider->search(array($this->object));

        $ids = array();
        foreach ($results as $result)
        {
            $ids[] = $result['id'];
        }
        $patients = Patient::model()->findAllByPk($ids);

        $this->assertEquals($expected, $patients);

        // Test unconfirmed diagnoses only.
        $this->object->textValue = 'Diabetes mellitus type 1';
        $this->object->isConfirmed = '0';

        $results = $this->searchProvider->search(array($this->object));

        $ids = array();
        foreach ($results as $result)
        {
            $ids[] = $result['id'];
        }
        $patients = Patient::model()->findAllByPk($ids);

        $this->assertEquals($expected, $patients);
    }*/
}
