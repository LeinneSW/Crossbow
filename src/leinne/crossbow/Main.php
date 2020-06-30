<?php

declare(strict_types=1);

namespace leinne\crossbow;

use leinne\crossbow\item\Crossbow;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

    public function onEnable() : void{
        ItemFactory::getInstance()->register(new Crossbow(ItemIds::CROSSBOW, 0, "Crossbow"));
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onReceive(DataPacketReceiveEvent $ev) : void{
        $session = $ev->getOrigin();
        $player = $session->getPlayer();
        $packet = $ev->getPacket();
        if(
            $packet instanceof InventoryTransactionPacket &&
            $packet->trData instanceof UseItemTransactionData &&
            $packet->trData->getActionType() === UseItemTransactionData::ACTION_CLICK_AIR
        ){
            $ev->setCancelled();

            $inv = $player->getInventory();
            $item = $inv->getItemInHand();
            if($player->isUsingItem() && !$item instanceof Crossbow){
                if(!$player->consumeHeldItem()){
                    $session->getInvManager()->syncSlot($inv, $inv->getHeldItemIndex());
                }
                return;
            }

            if(!$player->useHeldItem()){
                $session->getInvManager()->syncSlot($inv, $inv->getHeldItemIndex());
            }elseif($item->getNamedTag()->hasTag("chargedItem") && !$inv->getItemInHand()->getNamedTag()->hasTag("chargedItem")){
                $player->setUsingItem(false);
            }
        }
    }

}