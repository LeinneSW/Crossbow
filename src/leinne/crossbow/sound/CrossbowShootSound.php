<?php

declare(strict_types=1);

namespace leinne\crossbow\sound;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\world\sound\Sound;

class CrossbowShootSound implements Sound{

    public function encode(?Vector3 $pos) : array{
        return [
            LevelSoundEventPacket::create(LevelSoundEventPacket::SOUND_CROSSBOW_SHOOT, $pos)
        ];
    }
}