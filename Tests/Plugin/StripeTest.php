<?php
namespace Vespolina\Payment\StripeBundle\Tests\Plugin;

use JMS\Payment\CoreBundle\Model\PlanInterface;

class StripeTest extends \PHPUnit_Framework_TestCase
{
    protected $plugin;

    public function setup()
    {
        $this->plugin = $this->getPlugin();
    }

    public function testPlan()
    {
        $properties = array(
            'id' => 'plugin-test-create-plan',
            'amount' => 2,
            'currency' => 'usd',
            'interval' => PlanInterface::INTERVAL_MONTHLY,
            'name' => 'Gold'
        );
        $plan = $this->createPlan($properties);
        $response = $this->plugin->createPlan($plan, false);
        $this->assertInstanceof('\Stripe_Object', $response);
        // todo: changes response, then test for amount being set to 200

        // todo: test error
        // todo: updatePlan, by default only the name can be updated. how do we handle other changes

        $response = $this->plugin->deletePlan($plan, false);

        // todo: actually test delete, right now its just a clean up for create
    }

    public function testRecurring()
    {

        $this->plugin->initializeRecurring($recurringTransaction, false);


    }

    protected function createRecurringTransaction()
    {

    }

    protected function createPlan(array $properties)
    {
        $plan = $this->getMock('JMS\Payment\CoreBundle\Model\Plan',
            array('getId')
        );
        $id = isset($properties['id']) ? $properties['id'] : null;
        unset($properties['id']);
        $plan->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        foreach( $properties as $property => $value) {
            $setter = 'set' . ucfirst($property);
            $plan->$setter($value);
        }

        return $plan;
    }

    protected function getPlugin()
    {
        $mock = $this->getMockForAbstractClass(
            'Vespolina\Payment\StripeBundle\Plugin\StripePlugin',
            array('N1Inw9oVpCX6gCOAu8vu4Z9HZOH6vPKK')
        );

        return $mock;
    }
}
