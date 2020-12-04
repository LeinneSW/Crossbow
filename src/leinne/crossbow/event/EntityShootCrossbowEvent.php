<?php

declare(strict_types=1);

namespace leinne\crossbow\event;

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\entity\EntityEvent;
use pocketmine\item\Item;

class EntityShootCrossbowEvent extends EntityEvent implements Cancellable{
    use CancellableTrait;

    private Item $crossbow;

    private Entity $projectile;

    private float $force;

    public function __construct(Living $shooter, Item $crossbow, Projectile $projectile, float $force){
        $this->entity = $shooter;
        $this->crossbow = $crossbow;
        $this->projectile = $projectile;
        $this->force = $force;
    }

    /**
     * @return Living
     */
    public function getEntity(){
        return $this->entity;
    }

    public function getCrossbow() : Item{
        return $this->crossbow;
    }

    /**
     * Returns the entity considered as the projectile in this event.
     *
     * NOTE: This might not return a Projectile if a plugin modified the target entity.
     */
    public function getProjectile() : Entity{
        return $this->projectile;
    }

    public function setProjectile(Entity $projectile) : void{
        if($projectile !== $this->projectile){
            if(count($this->projectile->getViewers()) === 0){
                $this->projectile->close();
            }
            $this->projectile = $projectile;
        }
    }

    public function getForce() : float{
        return $this->force;
    }

    public function setForce(float $force) : void{
        $this->force = $force;
    }
}