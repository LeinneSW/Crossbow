<?php

declare(strict_types=1);

namespace leinne\crossbow;

use leinne\crossbow\item\Crossbow;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

    public function onEnable() : void{
        EnchantmentIdMap::getInstance()->register(EnchantmentIds::MULTISHOT, new Enchantment(1000, "%enchantment.multishot", Rarity::MYTHIC, ItemFlags::BOW, ItemFlags::NONE, 1));
        EnchantmentIdMap::getInstance()->register(EnchantmentIds::QUICK_CHARGE, new Enchantment(1001, "%enchantment.quick_charge", Rarity::MYTHIC, ItemFlags::BOW, ItemFlags::NONE, 3));

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

        $ev->cancel();
        $directionVector = $player->getDirectionVector();
        $ev = new PlayerItemUseEvent($player, $item, $directionVector);
        if($player->hasItemCooldown($item) or $player->isSpectator()){
            $ev->cancel();
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