<?php

namespace EzMinh\EXPShop;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase
{
    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->getResource("config.yml");
        if($this->getServer()->getPluginManager()->getPlugin("FormAPI") == null) {
            $this->getLogger()->warning(C::RED . "Please download FormAPI!");
            $this->getServer()->getPluginManager()->disablePlugin($this->getServer()->getPluginManager()->getPlugin("EXPShop"));
        } else {
            if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") == null) {
                $this->getLogger()->warning(C::RED . "Please download EconomyAPI!");
                $this->getServer()->getPluginManager()->disablePlugin($this->getServer()->getPluginManager()->getPlugin("EXPShop"));
            }
        }
    }
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool
    {
        if ($cmd->getName() == "expshop") 
        {
            if (!$sender instanceof Player) 
            {
                $sender->sendMessage(C::RED . "You can't not use this command here!");
                return false;
            }
            if (!$sender->hasPermission("expshop.cmd")) 
            {
                $sender->sendMessage(C::RED . "You don't have permission to use this command");
                return true;
            }
            $formapi = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
            $form = $formapi->createSimpleForm(function (Player $sender, $data) 
            {
                $result = $data;
                if ($result == null) 
                {
                }
                switch($result) 
                {
                    case '0':
                        $this->buyEXP($sender);
                    break;
                    case '1':
                        $this->sellEXP($sender);
                    break;
                }
            });
            $form->setTitle($this->getConfig()->get("mainform.title"));
            $form->addButton($this->getConfig()->get("buyexp.btn"));
            $form->addButton($this->getConfig()->get("sellexp.btn"));
            $form->sendToPlayer($sender);
        }
        return true;
    }
    public function buyEXP($sender)
    {
        $formapi = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $formapi->createCustomForm(function (Player $sender, $data){
            if(!is_numeric($data[0])) 
            {
                $sender->sendMessage($this->getConfig()->get("must_number"));
            } 
            else 
            {
                $economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                $player_money = $economyapi->myMoney($sender);
                $exp_cost_per_level = $this->getConfig()->get("buy_exp_price_per_level");
                $exp_level_cost = $data[0] * $exp_cost_per_level;
                if($player_money <= $exp_level_cost)
                {
                    $sender->sendMessage($this->getConfig()->get("enough_money"));
                } 
                else
                {
                    $economyapi->reduceMoney($sender, $exp_level_cost);
                    $player_exp = $sender->getXpLevel();
                    $total_exp_level = $player_exp + $data[0];
                    $sender->setXpLevel($total_exp_level);
                    $sender->sendMessage($this->getConfig()->get("buy_exp_success"));
                }
            }
        });
        $form->setTitle($this->getConfig()->get("buy_exp_form_title"));
        $form->addInput($this->getConfig()->get("buy_level_import"));
        $form->sendToPlayer($sender);
    }
    public function sellEXP($sender)
    {
        $formapi = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $formapi->createCustomForm(function (Player $sender, $data){
            if(!is_numeric($data[0])) 
            {
                $sender->sendMessage($this->getConfig()->get("must_number"));
            } 
            else 
            {
                $player_exp = $sender->getXpLevel();
                if($player_exp >= $data[0])
                {
                    $total_exp_level = $player_exp - $data[0];
                    $sender->setXpLevel($total_exp_level);
                    $sell_exp_cost = $this->getConfig()->get("sell_exp_cost_per_level");
                    $sender->sendMessage($this->getConfig()->get("sell_exp_success"));
                    $sell_exp_money = $data[0] * $sell_exp_cost;
                    $economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                    $economyapi->addMoney($sender, $sell_exp_money);
                } else {
                    $sender->sendMessage($this->getConfig()->get("enough_exp"));
                }
            }
        });
        $form->setTitle($this->getConfig()->get("sell_exp_form_title"));
        $form->addInput($this->getConfig()->get("sell_level_import"));
        $form->sendToPlayer($sender);
    }
}