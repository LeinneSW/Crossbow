<?php

declare(strict_types=1);

namespace leinne\crossbow;

use leinne\crossbow\enchant\QuickChargeEnchantment;
use leinne\crossbow\item\Crossbow;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

    public function onEnable() : void{
        Enchantment::register(new QuickChargeEnchantment(Enchantment::QUICK_CHARGE, "%enchantment.quick_charge", Enchantment::RARITY_MYTHIC, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, 3));
        ItemFactory::getInstance()->register(new Crossbow(new ItemIdentifier(ItemIds::CROSSBOW, 0), "Crossbow"));
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onReceive(DataPacketReceiveEvent $ev) : void{
        $session = $ev->getOrigin();
        $player = $session->getPlayer();
        $packet = $ev->getPacket();
        if(
            !$packet instanceof InventoryTransactionPacket ||
            !$packet->trData instanceof UseItemTransactionData ||
            !$packet->trData->getActionType() === UseItemTransactionData::ACTION_CLICK_AIR
        ){
            return;
        }
        $inv = $player->getInventory();
        $item = $inv->getItemInHand();
        if(!$item instanceof Crossbow)
            return;

        $ev->setCancelled();
        if(!$player->useHeldItem()){
            $session->getInvManager()->syncSlot($inv, $inv->getHeldItemIndex());
        }elseif($item->getNamedTag()->hasTag("chargedItem") && !$inv->getItemInHand()->getNamedTag()->hasTag("chargedItem")){
            $player->setUsingItem(false);
        }
    }

}