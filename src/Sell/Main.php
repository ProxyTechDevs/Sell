<?php
namespace Sell;
use onebone\economyapi\EconomyAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
class Main extends PluginBase implements Listener
{
	/** @var Config */
	private $messages , $sell;
	public function onEnable() : void
	{
		$files = array("sell.yml" , "messages.yml");
		foreach ($files as $file) {
			if (!file_exists($this->getDataFolder() . $file)) {
				@mkdir($this->getDataFolder());
				file_put_contents($this->getDataFolder() . $file , $this->getResource($file));
			}
		}
		$this->getServer()->getPluginManager()->registerEvents($this , $this);
		$this->sell = new Config($this->getDataFolder() . "sell.yml" , Config::YAML);
		$this->messages = new Config($this->getDataFolder() . "messages.yml" , Config::YAML);
	}
	
	/**
	 * @param CommandSender $sender
	 * @param Command       $cmd
	 * @param string        $label
	 * @param array         $args
	 * @return bool
	 */
	public function onCommand(CommandSender $sender , Command $cmd , string $label , array $args) : bool
	{
		switch (strtolower($cmd->getName())) {
			case "sell":
				/* Checks if command is executed by console. */
				/* It further solves the crash problem. */
				if (!($sender instanceof Player)) {
					$sender->sendMessage("use this command in-game");
				}
				/* Check if the player is permitted to use the command */
				if ($sender->hasPermission("sell") || $sender->hasPermission("sell.hand") || $sender->hasPermission("sell.all")) {
					/* Disallow non-survival mode abuse */
					if (!$sender->isSurvival()) {
						$sender->sendMessage("change your gamemode back to survival");
					}
					/* Sell Hand */
					if (isset($args[0]) && strtolower($args[0]) == "hand") {
						if (!$sender->hasPermission("sell.hand")) {
							$error_handPermission = $this->messages->get("error-nopermission-sellHand");
							$sender->sendMessage($error_handPermission);
						}
						$item = $sender->getInventory()->getItemInHand();
						$itemId = $item->getId();
						/* Check if the player is holding a block */
						if ($item->getId() === 0) {
							$sender->sendMessage("you arent holding any items");
						}
						/* Recheck if the item the player is holding is a block */
						if ($this->sell->get($itemId) == null) {
							$sender->sendMessage($item->getName()  cannot be sold");
						}
						/* Sell the item in the player's hand */
						EconomyAPI::getInstance()->addMoney($sender , $this->sell->get($itemId) * $item->getCount());
						$sender->getInventory()->removeItem($item);
						$price = $this->sell->get($item->getId()) * $item->getCount();
						$sender->sendMessage("$" . $price . " has been added to your account");
						$sender->sendMessage("sold for "$" $price " (" $item->getCount()  " "  $item->getName()  " at $"  $this->sell->get($itemId)  " each");
						/* Sell All */
					} elseif (isset($args[0]) && strtolower($args[0]) == "all") {
						if (!$sender->hasPermission("sell.all")) {
							$error_allPermission = $this->messages->get("error-nopermission-sellAll");
							$sender->sendMessage($error_allPermission);
						}
						$items = $sender->getInventory()->getContents();
						foreach ($items as $item) {
							if ($this->sell->get($item->getId()) !== null && $this->sell->get($item->getId()) > 0) {
								$price = $this->sell->get($item->getId()) * $item->getCount();
								EconomyAPI::getInstance()->addMoney($sender , $price);
								$sender->sendMessage(TF::GREEN . TF::BOLD . "(!) " . TF::RESET . TF::GREEN . "Sold for " . TF::RED . "$" . $price . TF::GREEN . " (" . $item->getCount() . " " . $item->getName() . " at $" . $this->sell->get($item->getId()) . " each).");
								$sender->getInventory()->remove($item);
							}
						}
					} elseif (isset($args[0]) && strtolower($args[0]) == "about") {
						$sender->sendMessage(TF::RED . TF::BOLD . "(!) " . TF::RESET . TF::GRAY . "This server uses the plugin, Sell Hand, by Muqsit Rayyan.");
					} else {
						$sender->sendMessage(TF::RED . TF::BOLD . "(!) " . TF::RESET . TF::DARK_RED . "Sell Online Market");
						$sender->sendMessage(TF::RED . "- " . TF::DARK_RED . "/sell hand " . TF::GRAY . "- Sell the item that's in your hand.");
						$sender->sendMessage(TF::RED . "- " . TF::DARK_RED . "/sell all " . TF::GRAY . "- Sell every possible thing in inventory.");
					}
				} else {
					$error_permission = $this->messages->get("error-permission");
					$sender->sendMessage(TF::RED . TF::BOLD . "Error: " . TF::RESET . TF::RED . $error_permission);
				}
		}
		return true;
	}
}
