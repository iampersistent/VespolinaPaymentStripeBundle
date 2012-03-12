<?php
/**
 * (c) 2012 Vespolina Project http://www.vespolina-project.org
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Vespolina\Payment\StripeBundle\Plugin;

use JMS\Payment\CoreBundle\Model\PlanInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;

class StripePlugin extends AbstractPlugin
{

    protected $apiKey;

    public static $intervalMapping = array(
//        PlanInterface::INTERVAL_DAILY => 'DAY',
//        PlanInterface::INTERVAL_WEEKLY => 'WEEK',
//        PlanInterface::INTERVAL_BIWEEKLY => 'BIWK',
//        PlanInterface::INTERVAL_SEMI_MONTHLY => 'SMMO',
//        PlanInterface::INTERVAL_ANNUALLY => 'FRWK',
        PlanInterface::INTERVAL_MONTHLY => 'month',
//        PlanInterface::INTERVAL_QUARTERLY => 'QTER',
//        PlanInterface::INTERVAL_ANNUALLY => 'SMYR',
        PlanInterface::INTERVAL_ANNUALLY => 'year',
    );

    public function __construct($apiKey)
    {

    }

    public function createPlan(PlanInterface $plan, $retry)
    {
        $arguments = array(
            'id' => $plan->getId(),
            'amount' => $plan->getAmount() * 100,
            'currency' => $plan->getCurrency(),
            'interval' => self::$intervalMapping[$plan->getInterval()],
            'name' => $plan->getName(),
            'trial_period_days' => $plan->getTrialPeriodDays(),
        );

        return $this->sendPlanRequest('create', $arguments);
    }

    public function processes($paymentSystemName)
    {
        return 'stripe' === $paymentSystemName;
    }

    public function isIndependentCreditSupported()
    {
        return false; // todo: this needs to be researched
    }

    protected function sendPlanRequest($method, $arguments)
    {
        \Stripe::setApiKey($this->apiKey);
        $response = \Stripe_Plan::$method($arguments);

        return $response;
    }
}
