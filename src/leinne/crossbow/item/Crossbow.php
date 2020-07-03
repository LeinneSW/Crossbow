<?php

declare(strict_types=1);

namespace leinne\crossbow\item;

use leinne\crossbow\event\EntityShootCrossbowEvent;
use leinne\crossbow\sound\CrossbowLoadingEndSound;
use leinne\crossbow\sound\CrossbowLoadingStartSound;
use leinne\crossbow\sound\CrossbowShootSound;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Arrow as ArrowItem;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\item\Tool;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Crossbow extends Tool{

    public function isCharged() : bool{
        return $this->getNamedTag()->hasTag("chargedItem");
    }

    public function getChargedItem() : ?Item{
        return ($tag = $this->getNamedTag()->getCompoundTag("chargedItem")) === null ? null : Item::nbtDeserialize($tag);
    }

    public function setCharged(?Item $item) : self{
        if($item === null || $item->isNull()){
            $this->getNamedTag()->removeTag("chargedItem");
        }elseif($item->getId() === ItemIds::FIREWORKS || $item instanceof ArrowItem){
            $this->getNamedTag()->setTag("chargedItem", $item->nbtSerialize()->setDouble("shootTime", microtime(true) + 0.26));
        }
        return $this;
    }

    public function onClickAir(Player $player, Vector3 $directionVector) : ItemUseResult{
        $nbt = $this->getNamedTag();
        $item = $nbt->getCompoundTag("chargedItem");
        $quickLevel = $this->getEnchantmentLevel(Enchantment::get(Enchantment::QUICK_CHARGE));
        if($item === null){
            $item = VanillaItems::ARROW();
            if($player->hasFiniteResources()){
                if(!$player->getInventory()->contains($item)){
                    $item = ItemFactory::getInstance()->get(ItemIds::FIREWORKS); //왼손 미구현
                    if(!$player->getInventory()->contains($item)){
                        return ItemUseResult::FAIL();
                    }
                }
            }

            $time = $player->getItemUseDuration();
            if($time >= 24 - $quickLevel * 5){
                if($player->hasFiniteResources()){
                    $player->getInventory()->removeItem($item);
                }
                $this->setCharged($item);
                $player->getWorld()->addSound($player->getLocation(), new CrossbowLoadingEndSound($quickLevel > 0));
            }else{
                $player->getWorld()->addSound($player->getLocation(), new CrossbowLoadingStartSound($quickLevel > 0));
            }
        }elseif($item->getDouble("shootTime", 0.0) >= microtime(true)){
            $player->getWorld()->addSound($player->getLocation(), new CrossbowLoadingEndSound($quickLevel > 0));
        }else{
            $item = Item::nbtDeserialize($item);
            $location = $player->getLocation();
            if($item instanceof ArrowItem){
                $entity = new Arrow(Location::fromObject(
                    $player->getEyePos(),
                    $player->getWorld(),
                    ($location->yaw > 180 ? 360 : 0) - $location->yaw,
                    -$location->pitch
                ), $player, true);
                if($player->isCreative(true)){
                    $entity->setPickupMode(Arrow::PICKUP_CREATIVE);
                }
            }elseif($item->getId() === ItemIds::FIREWORKS){
                //TODO: 폭죽 구현
                $entity = new Arrow(Location::fromObject(
                    $player->getEyePos(),
                    $player->getWorld(),
                    ($location->yaw > 180 ? 360 : 0) - $location->yaw,
                    -$location->pitch
                ), $player, true);
                if($player->isCreative(true)){
                    $entity->setPickupMode(Arrow::PICKUP_CREATIVE);
                }
            }else{
                $nbt->removeTag("chargeItem");
                return ItemUseResult::SUCCESS();
            }

            $multishot = $this->hasEnchantment(Enchantment::get(Enchantment::MULTISHOT));
            if($multishot){
                //TODO: 멀티샷 구현
            }
            $entity->setMotion($player->getDirectionVector());

            $ev = new EntityShootCrossbowEvent($player, $this, $entity, 7);
            $ev->call();

            $entity = $ev->getProjectile(); //This might have been changed by plugins

            if($ev->isCancelled()){
                $entity->flagForDespawn();
                return ItemUseResult::FAIL();
            }

            $entity->setMotion($entity->getMotion()->multiply($ev->getForce()));

            if($entity instanceof Projectile){
                $projectileEv = new ProjectileLaunchEvent($entity);
                $projectileEv->call();
                if($projectileEv->isCancelled()){
                    $ev->getProjectile()->flagForDespawn();
                    return ItemUseResult::FAIL();
                }

                $nbt->removeTag("chargedItem");
                $ev->getProjectile()->spawnToAll();
                $location->world->addSound($location, new CrossbowShootSound());
            }else{
                $entity->spawnToAll();
            }

            if($player->hasFiniteResources()){
                $this->applyDamage($multishot ? 3 : 1);
            }
        }
        return ItemUseResult::SUCCESS();
    }

    public function getMaxDurability() : int{
        return 464;
    }

    public function getFuelTime() : int{
        return 200;
    }

}