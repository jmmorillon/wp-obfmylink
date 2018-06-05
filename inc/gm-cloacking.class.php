<?php

/**
 * Description of gm-cloacking
 *
 * @author jean-michel
 * 
 * Ressource: https://udger.com/resources/ua-list/crawlers?c=1
 */
class gm_cloacking {

    private $botURL = NULL;
    private $botCode = NULL;
    private $userURL = NULL;
    private $userCode = NULL;
    private $hideFromGoogleServices = false;
    private $response = NULL;

    public function __construct() {
        
    }

    public function clkCheckIP($IP = NULL) {

        // Check du Host à partir de l'IP
        if (is_null($IP))
            $IP = $_SERVER['REMOTE_ADDR'];

        $IP = strip_tags($IP);
        $remoteHost = trim(gethostbyaddr($IP));

        if ($remoteHost == $IP) {
            // Pas de résolution, on check l'IP (Google uniquement)
            if (preg_match("/^64.233.*/i", $IP) || preg_match("/^66.102.*/i", $IP) || preg_match("/^66.249.*/i", $IP)
                    || preg_match("/^72.14.*/i", $IP) || preg_match("/^74.125.*/i", $IP) || preg_match("/^173.194.*/i", $IP) 
                    || preg_match("/^209.85.*/i", $IP) || preg_match("/^216.239.*/i", $IP)) {
                return true;
            } else {
                return false;
            }
        }

        // Vérification que c'est un bot
        $isBot = false;
        if ($this->hideFromGoogleServices) {
            if (preg_match('/\.google\.com ?$/i', $remoteHost)) {
                $isBot = true;
            }
        }
        if ((preg_match('/\.googlebot\.com$/i', $remoteHost))) {
            /** Google Bot * */
            $isBot = true;
        } else if ((preg_match('/\.yahoo\.net$/i', $remoteHost)) || (preg_match('/\.yahoo\.com$/i', $remoteHost)) || (preg_match('/\.yahoo\.net\.jp$/i', $remoteHost)) || (preg_match('/\.yahoo\-net\.jp$/i', $remoteHost)) || (preg_match('/\.yahoo\.co\.jp$/i', $remoteHost))
        ) {
            /** Yahoo Slurp * */
            $isBot = true;
        } else if ((preg_match('/\.orangebot\.orange\.fr$/i', $remoteHost)) || (preg_match('/\.voilabot\.orange\.fr$/i', $remoteHost)) || (preg_match('/\.fti\.net$/i', $remoteHost))
        ) {
            /** Orange Bot * */
            $isBot = true;
        } else if ((preg_match('/^msnbot\-[0-9\-]+\.search\.msn\.com$/i', $remoteHost))) {
            /** MSN / Bing * */
            $isBot = true;
        }

        // Vérification inverse de la corélation IP/Host
        $IPBack = gethostbyname($remoteHost);
        if ($isBot)
            if ($IPBack == $IP) {
                return true;
            }

        return false;
    }

    public function isBot() {
        if (!is_null($this->response))
            return $this->response;

        $this->response = $this->clkCheckIP();
        return $this->response;
    }

    /**
     * Considérer les services Google comme des bots (ex : translate)
     * !!!! preview du site dans search console.
     * @param type $value
     */
    public function setHideFromGoogleServices($value) {
        $this->hideFromGoogleServices = $value;
    }

}
