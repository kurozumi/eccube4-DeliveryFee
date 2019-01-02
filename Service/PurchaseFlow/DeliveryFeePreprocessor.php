<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Plugin\DeliveryFee\Service\PurchaseFlow;

use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor as BaseDeliveryFeePreprocessor;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Annotation\OrderFlow;
use Eccube\Entity\Order;

/**
 * 合計金額3000円未満の場合、送料に300円加算
 * 
 * @author Akira Kurozumi <info@a-zumi.net>
 * 
 * ご注文手続きページと受注管理で実行されるようアノテーションを設定
 * @ShoppingFlow
 * @OrderFlow
 */
class DeliveryFeePreprocessor implements ItemHolderPreprocessor {

    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!$itemHolder instanceof Order) {
            return;
        }
        
        // お届け先ごとに判定
        foreach ($itemHolder->getShippings() as $Shipping) {
            // 送料無料の受注明細かどうか確認
            foreach ($Shipping->getOrderItems() as $Item) {
                // 送料明細を探す
                if ($Item->getProcessorName() == BaseDeliveryFeePreprocessor::class) {
                    // 送料明細の数量が0の場合は送料無料
                    if ($Item->getQuantity() == 0) {
                        // 送料無料の場合は次の受注明細へ
                        continue 2;
                    }
                }
            }

            // 合計金額計算
            $total = 0;
            foreach ($Shipping->getProductOrderItems() as $Item) {
                $total += $Item->getPriceIncTax() * $Item->getQuantity();
            }

            // 合計金額が3000円未満の場合、送料に300円加算
            if ($total < 3000) {
                foreach ($Shipping->getOrderItems() as $Item) {
                    if ($Item->getProcessorName() == BaseDeliveryFeePreprocessor::class) {
                        $Item->setPrice($Item->getPrice() + 300);
                    }
                }
            }
        }
    }
}
