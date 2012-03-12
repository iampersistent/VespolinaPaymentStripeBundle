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

    public function testCreatePlan()
    {
        $properties = array(
            'id' => '12345',
            'amount' => 200,
            'currency' => 'usd',
            'interval' => PlanInterface::INTERVAL_MONTHLY,
            'name' => 'Gold'
        );
        $plan = $this->createPlan($properties);
        $response = $this->plugin->createPlan($plan, false);
        $this->assertInstanceof('\Stripe_Object', $response);
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
        $api = new \ReflectionProperty($mock, 'apiKey');
        $api->setAccessible(true);
        $api->setValue($mock, 'N1Inw9oVpCX6gCOAu8vu4Z9HZOH6vPKK');
        $api->setAccessible(false);

        return $mock;
    }
}
