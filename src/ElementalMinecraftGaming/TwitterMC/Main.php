<?php

namespace ElementalMinecraftGaming\TwitterMC;


use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\event\Listener;
use ElementalMinecraftGaming\TwitterMC\API\TwitterAPIExchange;

class Main extends PluginBase implements Listener {

    public $db;

    public function onEnable(): void {
        @mkdir($this->getDataFolder());
        $this->db = new \SQLite3($this->getDataFolder() . "Twitter.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS Twitter(user TEXT PRIMARY KEY, ak TEXT, aks TEXT, ck TEXT, cks TEXT);");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function accountCheck($user) {
        $username = \SQLite3::escapeString($user);
        $search = $this->db->prepare("SELECT * FROM Twitter WHERE user = :user;");
        $search->bindValue(":user", $username);
        $start = $search->execute();
        $checker = $start->fetchArray(SQLITE3_ASSOC);
        return empty($checker) == false;
    }
    
    public function setAuthKey($user, $key) {
        $del = $this->db->prepare("INSERT OR REPLACE INTO Twitter (user, ak) VALUES (:user, :ak);");
        $del->bindValue(":user", $user);
        $del->bindValue(":ak", $key);
        $start = $del->execute();
    }
    
    public function setAuthSecretKey($user, $key) {
        $del = $this->db->prepare("INSERT OR REPLACE INTO Twitter (user, ak, aks) VALUES (:user, :ak, :aks);");
        $del->bindValue(":user", $user);
        $del->bindValue(":ak", $this->getAuthKey($user));
        $del->bindValue(":aks", $key);
        $start = $del->execute();
    }
    
    public function setConsumerKey($user, $key) {
        $del = $this->db->prepare("INSERT OR REPLACE INTO Twitter (user, ak, aks, ck) VALUES (:user, :ak, :aks, :ck);");
        $del->bindValue(":user", $user);
        $del->bindValue(":ak", $this->getAuthKey($user));
        $del->bindValue(":aks", $this->getAuthSecretKey($user));
        $del->bindValue(":ck", $key);
        $start = $del->execute();
    }
    
    public function deleteSetup($user) {
        $del = $this->db->prepare("DELETE FROM Twitter where user='$user';");
        $start = $del->execute();
    }
    
    public function setConsumerSecretKey($user, $key) {
        $del = $this->db->prepare("INSERT OR REPLACE INTO Twitter (user, ak, aks, ck, cks) VALUES (:user, :ak, :aks, :ck, :cks);");
        $del->bindValue(":user", $user);
        $del->bindValue(":ak", $this->getAuthKey($user));
        $del->bindValue(":aks", $this->getAuthSecretKey($user));
        $del->bindValue(":ck", $this->getConsumerKey($user));
        $del->bindValue(":cks", $key);
        $start = $del->execute();
    }
    
    public function getAuthKey($user) {
        $search = $this->db->prepare("SELECT ak FROM Twitter WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $ak = $start->fetchArray(SQLITE3_ASSOC);
        return $ak["ak"];
    }
    
    public function getAuthSecretKey($user) {
        $search = $this->db->prepare("SELECT aks FROM Twitter WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $aks = $start->fetchArray(SQLITE3_ASSOC);
        return $aks["aks"];
    }
    
    public function getConsumerKey($user) {
        $search = $this->db->prepare("SELECT ck FROM Twitter WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $ck = $start->fetchArray(SQLITE3_ASSOC);
        return $ck["ck"];
    }
    
    public function getConsumerSecretKey($user) {
        $search = $this->db->prepare("SELECT cks FROM Twitter WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $cks = $start->fetchArray(SQLITE3_ASSOC);
        return $cks["cks"];
    }
    
    public function trustWarning($player) {
        $form = new SimpleForm(function (Player $player, $data) {
                    switch ($data) {
                        case 0:
                        $this->twitterLogin($player, $player->getName());
                        return;
                            
                        case 1:
                        return;
                            
                        case 2:
                        return;
                    }
                });
        $form->setTitle(TextFormat::RED . "WARNING!");
        $form->setContent(TextFormat::WHITE . "Bceause of how the API is there is no encryption except the default twitter one, do you trust the server owner?");
        $form->addButton(TextFormat::RED . "Yes");
        $form->addButton(TextFormat::RED . "No");
        $form->sendToPlayer($player);
        return true;
    }

    public function twitterLogin($sender, $user) {
        $form = new SimpleForm(function (Player $player, $data) use ($user) {
                    switch ($data) {
                        case 0:
                            $form = new CustomForm(function (Player $player, $data) use ($user) {
                                        if (!$data == null) {
                                            $this->setAuthKey($user, $data[0]);
                                            $form = new CustomForm(function (Player $player, $data) use ($user) {
                                                        if (!$data == null) {
                                                            $this->setAuthSecretKey($user, $data[0]);
                                                            $this->setConsumerKey($user, "92WdV7F56aAfsiTwdWqQT3Mvx");
                                                            $this->setConsumerSecretKey($user, "H6aj5TCptGBrLJJvMMqWTa0UDklXaWguQpMuQEGKkfQCchH0nQ");
                                                            $this->twitterPanel($player);
                                                            return true;
                                                        } else {
                                                            $player->sendMessage(TextFormat::RED . "Failed To Authenticate");
                                                            $this->deleteSetup($player->getName());
                                                            return;
                                                        }
                                                    });
                                            $form->setTitle(TextFormat::BLUE . "Access Token Secret");
                                            $form->addInput("");
                                            $form->sendToPlayer($player);
                                            return true;
                                        } else {
                                            $player->sendMessage(TextFormat::RED . "Failed To Authenticate");
                                            return;
                                        }
                                    });
                            $form->setTitle(TextFormat::BLUE . "Access Token");
                            $form->addInput("");
                            $form->sendToPlayer($player);
                            return;
                        case 1:
                            $form = new CustomForm(function (Player $player, $data) use ($user) {
                                        if (!$data == null) {
                                            $this->setAuthKey($user, $data[0]);
                                            $form = new CustomForm(function (Player $player, $data) use ($user) {
                                                        if (!$data == null) {
                                                            $this->setAuthSecretKey($user, $data[0]);
                                                            $form = new CustomForm(function (Player $player, $data) use ($user) {
                                                                        if (!$data == null) {
                                                                            $this->setConsumerKey($user, $data[0]);
                                                                            $form = new CustomForm(function (Player $player, $data) use ($user) {
                                                                                        if (!$data == null) {
                                                                                            $this->setConsumerSecretKey($user, $data[0]);
                                                                                            $this->twitterPanel($player);
                                                                                            return true;
                                                                                        } else {
                                                                                            $this->deleteSetup($player->getName());
                                                                                            $player->sendMessage(TextFormat::RED . "Failed To Authenticate");
                                                                                            return;
                                                                                        }
                                                                                    });
                                                                            $form->setTitle(TextFormat::BLUE . "Consumer Token Secret");
                                                                            $form->addInput("");
                                                                            $form->sendToPlayer($player);
                                                                            return true;
                                                                        } else {
                                                                            $this->deleteSetup($player->getName());
                                                                            $player->sendMessage(TextFormat::RED . "Failed To Authenticate");
                                                                            return;
                                                                        }
                                                                    });
                                                            $form->setTitle(TextFormat::BLUE . "Consumer Token");
                                                            $form->addInput("");
                                                            $form->sendToPlayer($player);
                                                            return true;
                                                        } else {
                                                            $this->deleteSetup($player->getName());
                                                            $player->sendMessage(TextFormat::RED . "Failed To Authenticate");
                                                            return;
                                                        }
                                                    });
                                            $form->setTitle(TextFormat::BLUE . "Access Token Secret");
                                            $form->addInput("");
                                            $form->sendToPlayer($player);
                                            return true;
                                        } else {
                                            $player->sendMessage(TextFormat::RED . "Failed To Authenticate");
                                            return;
                                        }
                                    });
                            $form->setTitle(TextFormat::BLUE . "Access Token");
                            $form->addInput("");
                            $form->sendToPlayer($player);
                            return;
                        case 2:
                            return;
                        case 3:
                            return;
                    }
                });
        $form->setTitle(TextFormat::BLUE . "Twitter");
        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Each bot has a 500k/mon limit, do you want to use the preset or your own?");
        $form->addButton(TextFormat::RED . "Preset");
        $form->addButton(TextFormat::RED . "My Own");
        $form->addButton(TextFormat::RED . "Cancel");
        $form->sendToPlayer($sender);
        return;
    }

    public function twitterPanel($user) {
        $form = new SimpleForm(function (Player $player, $data) {
                    switch ($data) {
                        case 0:
                            if ($player->hasPermission("twitter.post")) {
                            $this->twitterPostForm($player);
                            return true;
                            } else {
                                $player->sendMessage(TextFormat::RED . "You don't have posting permissions!");
                                return;
                            }
                        case 1:
                            return;
                        case 2:
                            return;
                    }
                });
        $form->setTitle(TextFormat::BLUE . "Twitter");
        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Choose your service.");
        $form->addButton(TextFormat::RED . "Post");
        $form->addButton(TextFormat::RED . "Exit");
        $form->sendToPlayer($user);
        return;
    }

    public function twitterPostForm($user) {
        $form = new CustomForm(function (Player $player, $data) use ($user) {
                    if (!$data == null) {
                        $this->twitterPost($player,$data[0]);
                        return true;
                    } else {
                        $player->sendMessage(TextFormat::RED . "Failed To Post");
                        return;
                    }
                });
        $form->setTitle(TextFormat::BLUE . "Message");
        $form->addInput("");
        $form->sendToPlayer($user);
        return true;
    }

    public function twitterPost($user, $message) {
        $oauth_access_token = $this->getAuthKey($user->getName());
        $oauth_access_token_secret = $this->getAuthSecretKey($user->getName());
        $consumer_key = $this->getConsumerKey($user->getName());
        $consumer_secret = $this->getConsumerSecretKey($user->getName());
        $url = 'https://api.twitter.com/1.1/statuses/update.json';
        $requestMethod = 'POST';
        $apiData = array(
            'status' => $message,
        );
        $twitter = new TwitterAPIExchange($oauth_access_token, $oauth_access_token_secret, $consumer_key, $consumer_secret);
        $twitter->buildOauth($url, $requestMethod);
        $twitter->setPostfields($apiData);
        $msg = $twitter->performRequest(true, array(CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (strtolower($command->getName()) == "twitter") {
            if ($sender->hasPermission("twitter.use")) {
                if ($sender instanceof Player) {
                    $user = $sender->getName();
                    if ($this->accountCheck($user) == true) {
                        $this->twitterPanel($sender);
                        $sender->sendMessage(TextFormat::BLUE . "Successful Login!");
                        return true;
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Auto-Login Failed!");
                        $this->trustWarning($sender);
                        //$this->twitterPost($sender, "testing");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Please be in-game to use!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Invalid Permissions!");
                return false;
            }
        }
        return false;
    }

}
