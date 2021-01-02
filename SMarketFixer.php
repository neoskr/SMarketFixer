<?php

/**
 * @name SMarketFixer
 * @main SMarketFixer\SMarketFixer
 * @author SYNK
 * @version x
 * @api 3.0.0
 * @description (!)
 * @permissions: [fix.perm: [default: OP]]
 */
 

namespace SMarketFixer;


use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\scheduler\ClosureTask;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use solo\smarket\SMarket;

use pocketmine\item\Item;
use pocketmine\block\ItemFrame;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;


class SMarketFixer extends PluginBase implements Listener
{


	public static $prefix = "§6§l[알림] §r§7";
	
	protected static $item = null;
	
	public static $pos1 = [];
	public static $pos2 = [];


    public function onEnable ()
    {
		
		self::$item = Item::get (Item::IRON_AXE, 1);
		self::$item->setCustomName ("§a상점 픽스 도끼");
		
		$this->getScheduler()->scheduleDelayedTask (new ClosureTask (function (int $currentTick) : void
		{

			Item::addCreativeItem (self::$item);
			
		}), 20);

		$this->getServer()->getCommandMap()->register ($this->getName(), new class extends Command
		{

			public function __construct ()
			{

				parent::__construct ('상점재설치', '', '', ['marketfix']);
				$this->setPermission ('fix.perm');

			}

			public function execute (CommandSender $player, string $label, array $args)
			{
				
				if (! $player instanceof Player)
					return $player->sendMessage ('인-게임 안에서만 사용할 수 있습니다.');

				if (! $player->hasPermission ($this->getPermission()))
					return $player->sendMessage (SMarketFixer::$prefix . '명령어를 사용할 권한이 없습니다.');
				
				if (! isset (SMarketFixer::$pos1 [$player->getName()]))
					return $player->sendMessage (SMarketFixer::$prefix . '첫 번째 지점을 선택해주세요.');
					
				if (! isset (SMarketFixer::$pos2 [$player->getName()]))
					return $player>sendMessage (SMarketFixer::$prefix . '두 번째 지점을 선택해주세요.');
					
				if (SMarketFixer::$pos1 [$player->getName()]['lv'] !== SMarketFixer::$pos2 [$player->getName()]['lv'])
					return $player->sendMessage (SMarketFixer::$prefix . '두 지점은 같은 월드에 있어야 합니다.');
					
				$xList = [SMarketFixer::$pos1 [$player->getName()]['x'], SMarketFixer::$pos2 [$player->getName()]['x']];
				$yList = [SMarketFixer::$pos1 [$player->getName()]['y'], SMarketFixer::$pos2 [$player->getName()]['y']];
				$zList = [SMarketFixer::$pos1 [$player->getName()]['z'], SMarketFixer::$pos2 [$player->getName()]['z']];
				
				$level = SMarketFixer::$pos1 [$player->getName()]['lv'];
				
				unset (SMarketFixer::$pos1 [$player->getName()]);
				unset (SMarketFixer::$pos2 [$player->getName()]);
				
				$error = 0;
				$succeed = 0;

				for ($x = min($xList); $x <= max($xList); $x++)
				{

					for ($y = min($yList); $y <= max($yList); $y++)
					{

						for ($z = min($zList); $z <= max($zList); $z++)
						{

							if (($block = $level->getBlockAt ($x, $y, $z)) instanceof ItemFrame)
							{

								$tile = $level->getTile ($block);

								if ($tile === null)
								{
									
									$error ++;

									$player->sendMessage (SMarketFixer::$prefix . "알 수 없는 오류로 아이템 액자가 발견되었지만 작업을 처리할 수 없습니다 (x={$x}, y={$y}, z={$z})");
									continue;

								}
					
								$item = $tile->getItem();
								
								if ($item->isNull())
									continue;

								$item->clearNamedTag();
								$tile->setItem ($item);

								$market = SMarket::getInstance()->getMarketFactory()->getMarketByItem ($item);
								SMarket::getInstance()->getMarketManager()->setMarket($block, $market, true);
								
								$market->updateTile($tile);
								
								$player->sendMessage (SMarketFixer::$prefix . "아이템 §a{$item->getName()}§r§7(을)를 성공적으로 복구 했습니다 (x={$x}, y={$y}, z={$z})");
								$succeed ++;

							}
							
						}

					}

				}
				
				$player->sendMessage (SMarketFixer::$prefix . "결과: 오류 §a{$error}§7개, 성공 §a{$succeed}§7개 처리 완료");

			}

		});
		
		Server::getInstance()->getPluginManager()->registerEvents ($this, $this);

    }
	
	public function onTouch (PlayerInteractEvent $event)
	{
		
		if (! $event->getItem()->equals (self::$item))
			return;
			
		$player = $event->getPlayer();
		
		if (! $player->hasPermission ('fix.perm'))
			return $player->sendMessage (self::$prefix . '이 도끼를 사용할 권한이 없습니다.');
			
		$block = $event->getBlock();
		$event->setCancelled (true);
			
		self::$pos2 [$player->getName()] = ['x' => $block->x, 'y' => $block->y, 'z' => $block->z, 'lv' => $block->level];
		$player->sendMessage (self::$prefix . '두 번째 지점이 설정되었습니다.');
		
	}
	
	public function onBreak (BlockBreakEvent $event)
	{
		
		if (! $event->getItem()->equals (self::$item))
			return;
			
		$player = $event->getPlayer();
		
		if (! $player->hasPermission ('fix.perm'))
			return $player->sendMessage (self::$prefix . '이 도끼를 사용할 권한이 없습니다.');
			
		$block = $event->getBlock();
		$event->setCancelled (true);
			
		self::$pos1 [$player->getName()] = ['x' => $block->x, 'y' => $block->y, 'z' => $block->z, 'lv' => $block->level];
		$player->sendMessage (self::$prefix . '첫 번째 지점이 설정되었습니다.');
		
	}

}