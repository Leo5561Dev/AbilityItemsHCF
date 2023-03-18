<?php

namespace Leo\AbilityItems;

use Leo\AbilityItems\Commands\AbilitysCommand;
use Leo\AbilityItems\Commands\PartnerItemsCommand;
use Leo\AbilityItems\entity\NPCEntity;
use Leo\AbilityItems\Listener\DamageListener;
use Leo\AbilityItems\Listener\EventListener;
use Leo\AbilityItems\Listener\ItemUseListener;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\event\Listener;
use pocketmine\item\FishingRod;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

class Main extends PluginBase
{
    use SingletonTrait;

    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    /** @var Item[] */
    private array $saved_items = [];

    private array $cooldowns = [
        'AntiFall' => [],
        'ComboAbility' => [],
        'Anhilator' => [],
        'SecondChance' => [],

        'PartnerItems' => []
    ];

    protected function onEnable(): void
    {

        $this->saveResource("config.yml");

        $this->getServer()->getCommandMap()->register("abilitys", new AbilitysCommand());
        $this->getServer()->getCommandMap()->register("partneritems", new PartnerItemsCommand());

        EntityFactory::getInstance()->register(NPCEntity::class, function (World $world, CompoundTag $nbt): NPCEntity {
            return new NPCEntity(EntityDataHelper::parseLocation($nbt, $world), NPCEntity::parseSkinNBT($nbt), $nbt);
        }, ['AbilityNPCEntity']);

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        $this->registerListener(new ItemUseListener());
        $this->registerListener(new DamageListener());
        $this->registerListener(new EventListener());

    }

    private function registerListener(Listener $listener): void
    {
        $this->getServer()->getPluginManager()->registerEvents($listener, $this);
    }

    public function inCooldown(string $type, string $player): bool
    {
        if (isset($this->cooldowns[$type]) && isset($this->cooldowns[$type][$player])) {
            return $this->cooldowns[$type][$player] > time();
        }
        return false;
    }

    public function getCooldown(string $type, string $player): int
    {
        return $this->cooldowns[$type][$player] - time();
    }

    public function addCooldown(string $type, string $player, int $time): void
    {
        $this->cooldowns[$type][$player] = time() + $time;
    }
    /**
     * @return Item[]
     */
    public function getSavedItems(): array {
        return $this->saved_items;
    }

    public function getSavedItemByCustomName(string $custom_name): ?Item {
        return $this->saved_items[$custom_name] ?? null;
    }

    public function addSavedItem(Item $item): void {
        $this->saved_items[$item->getCustomName()] = $item;
    }
}
