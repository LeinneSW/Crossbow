<?php

declare(strict_types=1);

namespace leinne\crossbow;

use leinne\crossbow\item\Crossbow;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

    public function onEnable() : void{
        Enchantment::register(new Enchantment(Enchantment::MULTISHOT, "%enchantment.multishot", Enchantment::RARITY_MYTHIC, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, 1));
        Enchantment::register(new Enchantment(Enchantment::QUICK_CHARGE, "%enchantment.quick_charge", Enchantment::RARITY_MYTHIC, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, 3));
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
            $packet->trData->getActionType() !== UseItemTransactionData::ACTION_CLICK_AIR
        ){
            return;
        }

        $inv = $player->getInventory();
        $item = $inv->getItemInHand();
        if(!$item instanceof Crossbow)
            return;

        $ev->setCancelled();
        $directionVector = $player->getDirectionVector();
        $ev = new PlayerItemUseEvent($player, $item, $directionVector);
        if($player->hasItemCooldown($item) or $player->isSpectator()){
            $ev->setCancelled();
        }

        $ev->call();

        if($ev->isCancelled()){
            $session->getInvManager()->syncSlot($inv, $inv->getHeldItemIndex());
            return;
        }

        $oldItem = clone $item;
        $result = $item->onClickAir($player, $directionVector);
        if($result->equals(ItemUseResult::FAIL())){
            $session->getInvManager()->syncSlot($inv, $inv->getHeldItemIndex());
            return;
        }

        $player->resetItemCooldown($item);
        $inv->setItemInHand($item);

        if(!$oldItem->isCharged() && !$item->isCharged()){
            $player->setUsingItem(true);
        }else{
            $player->setUsingItem(false);
        }
    }

}