<?php
namespace NeosRulez\Shop\CouponFinisher\Domain\Finisher;

/*
 * This file is part of the NeosRulez.Shop.CouponFinisher package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeTemplate;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Utility\Algorithms;
use Neos\Neos\Utility\NodeUriPathSegmentGenerator;
use NeosRulez\Shop\CouponFinisher\Domain\Service\MailService;
use NeosRulez\Shop\Domain\Model\Cart;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use NeosRulez\Shop\Domain\Repository\OrderRepository;

/**
 * @Flow\Scope("singleton")
 */
class CouponFinisher
{

    /**
     * @Flow\Inject
     * @var Cart
     */
    protected $cart;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeUriPathSegmentGenerator
     */
    protected $uriPathSegmentGenerator;

    /**
     * @Flow\Inject
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @Flow\Inject
     * @var MailService
     */
    protected $mailService;

    /**
     * @param mixed $args
     * @return void
     */
    public function execute(mixed $args): void
    {
        if(get_class($args) !== 'NeosRulez\Shop\Domain\Model\Order') {
            $arguments = json_decode($args, true);
        } else {
            $arguments['order_number'] = $args->getOrdernumber();
            $invoiceData = json_decode($args->getInvoicedata(), true);
            $arguments['email'] = $invoiceData['email'];
        }

        $order = $this->orderRepository->findByOrdernumber($arguments['order_number']);
        $context = $this->contextFactory->create();

        if(!empty($order)) {
            $cart = json_decode($order->getCart(), true);
            if(!empty($cart)) {
                foreach ($cart as $item) {

                    $node = $context->getNodeByIdentifier($item['node']);

                    if($node->hasProperty('createCouponAfterPayment')) {

                        for ($i = 0; $i < $item['quantity']; $i++) {
                            $couponCode = $this->createCouponNode($node);

                            $this->mailService->sendMail(
                                [
                                    'code' => $couponCode,
                                    'node' => $node
                                ],
                                $node->getProperty('mailSubject'),
                                $arguments['email']
                            );
                        }

                    }

                }
            }
        }
    }

    /**
     * @param NodeInterface $node
     * @return string
     */
    private function createCouponNode(NodeInterface $node): string
    {
        $targetNode = $node->getProperty('storagePoint');
        $newNode = new NodeTemplate();
        $nodeType = 'NeosRulez.Shop:Document.Coupon';
        $newNode->setNodeType($this->nodeTypeManager->getNodeType($nodeType));

        $code = Algorithms::generateRandomString(8);
        $newNode->setProperty('title', $code);
        $newNode->setProperty('code', $code);
        $newNode->setProperty('isShippingCoupon', $node->getProperty('isShippingCoupon'));
        $newNode->setProperty('percentual', $node->getProperty('percentual'));
        $newNode->setProperty('value', $node->getProperty('value'));
        $newNode->setProperty('min_cart_value', $node->getProperty('cartMinValue'));
        $newNode->setProperty('quantity_restriction', true);
        $newNode->setProperty('quantity', '1');

        $createdNode = $targetNode->createNodeFromTemplate($newNode);

        $uriPathSegment = $this->uriPathSegmentGenerator->generateUriPathSegment($createdNode);
        $createdNode->setProperty('uriPathSegment', $uriPathSegment);

        return $createdNode->getProperty('code');
    }

}
