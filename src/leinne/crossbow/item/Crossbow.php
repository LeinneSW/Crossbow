<?php

declare(strict_types=1);

namespace leinne\crossbow\item;

use leinne\crossbow\sound\CrossbowLoadingEndSound;
use leinne\crossbow\sound\CrossbowLoadingStartSound;
use leinne\crossbow\sound\CrossbowShootSound;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\ItemUseResult;
use pocketmine\item\Tool;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\BowShootSound;

class Crossbow extends Tool{
    /**
     * Returns the maximum amount of damage this item can take before it breaks.
     */
    public function getMaxDurability() : int{
        return 464;
    }

    public function getFuelTime() : int{
        return 200;
    }

    public function onClickAir(Player $player, Vector3 $directionVector) : ItemUseResult{
        $nbt = $this->getNamedTag();
        $arrow = $nbt->getCompoundTag("chargedItem");
        if($arrow === null){
            $item = VanillaItems::ARROW();
            if($player->hasFiniteResources() and !$player->getInventory()->contains($item)){
                return ItemUseResult::FAIL();
            }

            $time = $player->getItemUseDuration();
            if($time >= 23){
                if($player->hasFiniteResources()){
                    $player->getInventory()->removeItem($item);
                }
                $tag = $item->nbtSerialize();
                $tag->removeTag("id");
                $tag->setDouble("chargedTime", microtime(true) + 0.25);
                $tag->setString("Name", "minecraft:arrow");
                $nbt->setTag("chargedItem", $tag);
                $player->getWorld()->addSound($player->getLocation(), new CrossbowLoadingEndSound());
            }else{
                $player->getWorld()->addSound($player->getLocation(), new CrossbowLoadingStartSound());
            }
        }elseif($arrow->getDouble("chargedTime") < microtime(true)){
            $location = $player->getLocation();

            $entity = new Arrow(Location::fromObject(
                $player->getEyePos(),
                $player->getWorld(),
                ($location->yaw > 180 ? 360 : 0) - $location->yaw,
                -$location->pitch
            ), $player, true);
            $entity->setMotion($player->getDirectionVector());

            $infinity = $this->hasEnchantment(Enchantment::INFINITY());
            if($infinity){
                $entity->setPickupMode(Arrow::PICKUP_CREATIVE);
            }
            if(($punchLevel = $this->getEnchantmentLevel(Enchantment::PUNCH())) > 0){
                $entity->setPunchKnockback($punchLevel);
            }
            if(($powerLevel = $this->getEnchantmentLevel(Enchantment::POWER())) > 0){
                $entity->setBaseDamage($entity->getBaseDamage() + (($powerLevel + 1) / 2));
            }
            if($this->hasEnchantment(Enchantment::FLAME())){
                $entity->setOnFire(intdiv($entity->getFireTicks(), 20) + 100);
            }
            $ev = new EntityShootBowEvent($player, $this, $entity, 7);
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

                $ev->getProjectile()->spawnToAll();
                $location->getWorldNonNull()->addSound($location, new BowShootSound());
            }else{
                $entity->spawnToAll();
            }

            if($player->hasFiniteResources()){
                $this->applyDamage(1);
            }
            $nbt->removeTag("chargedItem");
            $player->getWorld()->addSound($player->getLocation(), new CrossbowShootSound());
        }
        return ItemUseResult::SUCCESS();
    }

}