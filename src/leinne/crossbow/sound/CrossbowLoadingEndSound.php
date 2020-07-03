<?php

declare(strict_types=1);

namespace leinne\crossbow\sound;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\world\sound\Sound;

class CrossbowLoadingEndSound implements Sound{

    private $quick;

    public function __construct(bool $quick = false){
        $this->quick = $quick;
    }

    public function encode(?Vector3 $pos){
        return LevelSoundEventPacket::create(
            $this->quick ? LevelSoundEventPacket::SOUND_CROSSBOW_QUICK_CHARGE_END : LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_END,
            $pos
        );
    }
}