<?php
/**
 * (c) 2012 Vespolina Project http://www.vespolina-project.org
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Vespolina\Payment\StripeBundle\Plugin;

use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Model\CreditCardProfileInterface;
use JMS\Payment\CoreBundle\Model\PlanInterface;
use JMS\Payment\CoreBundle\Model\RecurringInstructionInterface;
use JMS\Payment\CoreBundle\Model\RecurringTransactionInterface;

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
        $this->apiKey = $apiKey;
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

    public function deletePlan(PlanInterface $plan, $retry)
    {
        $stripePlan = $this->retrievePlan($plan->getId(), $retry);

        return $stripePlan->delete();
    }

    function initializeRecurring(RecurringInstructionInterface $instruction, $retry)
    {
        $creditCardProfile = $instruction->getCreditCardProfile();
        $response = $this->createChargeToken($creditCardProfile);

        $arguments = array(
            'card' => $response['id'],
            'plan' => $instruction->getProviderPlanId(),
            'email' => $creditCardProfile->getEmail()
        );

        $response = $this->sendCustomerRequest('create', $arguments);

        // todo: obviously this is wrong, it should be configurable
        $transaction = new \JMS\Payment\CoreBundle\Document\RecurringTransaction();

        $transaction->setAmount($instruction->getAmount());
        $transaction->setBillingFrequency($instruction->getBillingFrequency());
        $transaction->setBillingInterval($instruction->getBillingInterval());
        $transaction->setCreditCardProfile($creditCardProfile);
        $transaction->setCurrency($instruction->getCurrency());
        $transaction->setPlanId($instruction->getProviderPlanId());
        $transaction->setProcessor('stripe');
        $processable = $response->__toArray(true);
        $transaction->setProcessorId($processable['id']);
        $transaction->addResponseData($processable);

        return $transaction;
    }

    public function processes($paymentSystemName)
    {
        return 'stripe' === $paymentSystemName;
    }

    public function retrievePlan($id, $retry)
    {
        return $this->sendPlanRequest('retrieve', $id);
    }

    public function isIndependentCreditSupported()
    {
        return false; // todo: this needs to be researched
    }

    protected function createChargeToken(CreditCardProfileInterface $creditCard)
    {
        $expiration = $creditCard->getExpiration();

        $arguments = array(
            "card" => array(
                "number" => $creditCard->getCardNumber('active'),
                "exp_month" => $expiration['month'],
                "exp_year" => $expiration['year'],
                "cvc" => $creditCard->getCvv(),
                'name' => $creditCard->getName(),
                'address_line1' => $creditCard->getStreet1(),
                'address_line2' => $creditCard->getStreet2(),
                'address_zip' => $creditCard->getPostcode(),
                'address_state' => $creditCard->getState(),
                'address_country' => $creditCard->getCountry(),
            ),
            "currency" => "usd",
        );

        $response = $this->sendTokenRequest('create', $arguments);

        return $response;
    }

    protected function sendCustomerRequest($method, $arguments)
    {
        \Stripe::setApiKey($this->apiKey);
        $response = \Stripe_Customer::$method($arguments);

        return $response;
    }

    protected function sendPlanRequest($method, $arguments)
    {
        \Stripe::setApiKey($this->apiKey);
        $response = \Stripe_Plan::$method($arguments);

        return $response;
    }

    protected function sendTokenRequest($method, $arguments)
    {
        \Stripe::setApiKey($this->apiKey);
        $response = \Stripe_Token::$method($arguments);

        return $response;
    }


}
