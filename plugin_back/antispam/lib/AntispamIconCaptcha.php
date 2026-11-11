<?php

use Template\Template;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

class AntispamIconCaptcha extends AntispamAbstractCaptcha {

    protected array $icons = [
        'fingerprint',
        'dragon',
        'cubes',
        'igloo',
        'fish',
        'flask'
    ];

    protected bool $lessOrMore = false;

    protected array $choosenIcons = [];

    protected array $IconsToDisplay = [];

    protected string $result = '';

    /**
     * Return the html code for the icon captcha.
     *
     * @return string The html code.
     */
    public function getText(): string {
        if (empty($this->IconsToDisplay)) {
            $this->generate();
        }
        $tpl = new Template(PLUGINS . 'antispam' . DS . 'template' . DS .'captcha-icon.tpl');
        $tpl->set('IconsToDisplay', $this->IconsToDisplay);
        $tpl->set('lessOrMore', $this->lessOrMore);
        return $tpl->output() . $this->getGenericHtml();
    }

    /**
     * Return true if the icon captcha is valid.
     *
     * @return bool True if the captcha is valid, false otherwise.
     */
    public function isValid(): bool {
        return (isset($_SESSION['antispam_result']) && isset($_POST['iconCaptcha']) && $_SESSION['antispam_result'] === sha1($_POST['iconCaptcha']) && $this->isGenericValid());
    }

    /**
     * Generate the icon captcha.
     *
     * The generation of the icon captcha is done like this :
     * - Select 3 icons from the list of icons.
     * - Add the first icon 1 time in the list.
     * - Add the second icon 2 times in the list.
     * - Add the third icon 3 times in the list.
     * - Randomize the order of the icons.
     * - If the $lessOrMore variable is true, then the result is the more present icon, else the result is the less present icon.
     * - Save the result in the session.
     */
    protected function generate(): void {
        $this->choosenIcons = array_rand($this->icons, 3);
        $this->lessOrMore = rand(0, 1);
        $j = -1;
        foreach ($this->choosenIcons as $key => $iconId) {
            for ($i = 0; $i < $key + 1 ; $i++) {
                $j++;
                $this->IconsToDisplay[$j]['id'] = $this->choosenIcons[$key];
                $this->IconsToDisplay[$j]['rotate'] = (rand(0, 3) * 90);
                $this->IconsToDisplay[$j]['name'] = $this->icons[$this->IconsToDisplay[$j]['id']];
            }
        }
        
        $this->result = $this->lessOrMore ? $this->IconsToDisplay[5]['id'] : $this->IconsToDisplay[0]['id'];

        shuffle($this->IconsToDisplay);

        $_SESSION['antispam_result'] = sha1($this->result);
    }

}