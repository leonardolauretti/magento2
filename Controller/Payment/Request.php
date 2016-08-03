<?php
/**
 * 2007-2016 [PagSeguro Internet Ltda.]
 *
 * NOTICE OF LICENSE
 *
 *Licensed under the Apache License, Version 2.0 (the "License");
 *you may not use this file except in compliance with the License.
 *You may obtain a copy of the License at
 *
 *http://www.apache.org/licenses/LICENSE-2.0
 *
 *Unless required by applicable law or agreed to in writing, software
 *distributed under the License is distributed on an "AS IS" BASIS,
 *WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *See the License for the specific language governing permissions and
 *limitations under the License.
 *
 *  @author    PagSeguro Internet Ltda.
 *  @copyright 2016 PagSeguro Internet Ltda.
 *  @license   http://www.apache.org/licenses/LICENSE-2.0
 */

namespace UOL\PagSeguro\Controller\Payment;

use UOL\PagSeguro\Model\PaymentMethod;

/**
 * Class Request
 *
 * @package UOL\PagSeguro\Controller\Payment
 */
class Request extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var \UOL\PagSeguro\Model\PaymentMethod
     */
    private $payment;

    /**
     * Request constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);

        $this->_checkoutSession = $this->_objectManager
            ->create('\Magento\Checkout\Model\Session');

        $this->payment = new PaymentMethod(
            $this->_objectManager
                ->create('\Magento\Framework\App\Config\ScopeConfigInterface'),
            $this->_checkoutSession,
            $this->_objectManager
                ->create('\Magento\Directory\Api\CountryInformationAcquirerInterface'),
            $this->_objectManager->create('Magento\Framework\Module\ModuleList')
        );
    }

    /**
     * Redirect to payment
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        try {
            return $this->resultRedirectFactory->create()->setPath($this->payment->createPaymentRequest());
        } catch (\Exception $exception) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->_objectManager->create('\Magento\Sales\Model\Order')->load(
                $this->_checkoutSession->getLastRealOrder()->getId()
            );
            /** change payment status in magento */
            $order->addStatusToHistory('pagseguro_cancelada', null, true);
            /** save order */
            $order->save();

            return $this->_redirect('pagseguro/payment/failure');
        }
    }
}
