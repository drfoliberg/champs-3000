<?php

/**
 * @author Simon Bernier St-Pierre
 * Modifié par
 * @author Justin Duplessis
 * 
 * @version v0.2.1 Ajout erreurs select input et suppression du champ ingorerSiVide
 * @todo Mettre à jour avec le nouveau format d'erreurs les autres Input que Text, ajouter l'option de différents formats de date, téléphone etc.
 * 
 * Publié sous la license MIT
 * 
 * Obtenez la dernière version https://github.com/drfoliberg/champs-3000/
 */
interface Test {

    function test($text);
}

class TestRequis implements Test {

    function test($text) {
        //return strlen($text) == 0 ? 1 : 0;
        $err = ['erreur' => false];
        if (strlen($text) == 0) {
            $err = [
                'erreur' => true,
                'nom_erreur' => 'requis'
            ];
        }
        return $err;
    }

}

class TestMin implements Test {

    var $min;

    function __construct($min) {
        $this->min = $min;
    }

    function test($text) {
        $err = ['erreur' => false];
        if (strlen($text) < $this->min) {
            $err = [
                'erreur' => true,
                'nom_erreur' => 'trop_court',
                'remplacements' => [$this->min]
            ];
        }
        return $err;
    }

}

class TestMax implements Test {

    var $max;

    function __construct($max) {
        $this->max = $max;
    }

    function test($text) {
        $err = ['erreur' => false];
        if (strlen($text) > $this->max) {
            $err = [
                'erreur' => true,
                'nom_erreur' => 'trop_long',
                'remplacements' => [$this->max]
            ];
        }
        return $err;
    }

}

class TestSize implements Test {

    var $min;
    var $max;

    function __construct($min, $max) {
        $this->min = $min;
        $this->max = $max;
    }

    function test($text) {
        //return strlen($text) >= $this->min && strlen($text) <= $this->max ? 0 : 1;
        $err = ['erreur' => false];
        if (!(strlen($text) >= $this->min && strlen($text) <= $this->max)) {
            $err = [
                'erreur' => true,
                'nom_erreur' => 'size',
                'remplacements' => [$this->max, $this->min]
            ];
        }
        return $err;
    }

}

class TestEmail implements Test {

    function test($text) {
        $err = ['erreur' => false];
        if (!preg_match("/^.+@.+\..+$/", $text)) {
            $err = [
                'erreur' => true,
                'nom_erreur' => 'email'
            ];
        }
        return $err;
    }

}

class TestDate implements Test {

    function test($text) {
        global $label;
        $err = ['erreur' => false];
        $exprdate = "/^(\d{4})\-(\d\\d)\-(\d\d)$/";
        if (preg_match($exprdate, $text, $matches) === 1) {
            $year = intval($matches[1]);
            $month = intval($matches[2]);
            $day = intval($matches[3]);
            if (!checkdate($month, $day, $year)) {
                $err = [
                    'erreur' => true,
                    'nom_erreur' => 'date',
                    'remplacements' => [$label['format_date']]
                ];
            }
        } else {
            $err = [
                'erreur' => true,
                'nom_erreur' => 'date',
                'remplacements' => [$label['format_date']]
            ];
        }
        return $err;
    }

}

class TestTelephone implements Test {

    function test($text) {
        $formatTel = "123-123-1234";
        $err = ['erreur' => false];
        if (!preg_match("/^\d{3}\-\d{3}\-\d{4}$/", $text)) {
            $err = [
                'erreur' => true,
                'nom_erreur' => 'tel',
                'remplacements' => [$formatTel]
            ];
        }
        return $err;
    }

}

class TestCodePostal implements Test {

    function test($text) {
        $formatsCodePostal = ['a1a 1a1', 'a1a1a1'];
        $err = ['erreur' => false];

        if (!preg_match("/^[[:alpha:]]\d[[:alpha:]]\s?\d[[:alpha:]]\d$/", $text)) {
            $err = [
                'erreur' => true,
                'nom_erreur' => 'tel',
                'remplacements' => [join(";", $formatsCodePostal)]
            ];
        }
        return $err;
    }

}

abstract class Input {

    var $nom;
    var $value;
    var $erreurs;

    protected function __construct($nom) {
        $this->nom = $nom;
        $this->value = isset($_POST[$nom]) ? $_POST[$nom] : null;
    }

    public function has_value() {
        return $this->value !== null;
    }

    public function valider() {
        return 0;
    }

    abstract function afficher();
}

class TextInput extends Input {

    //var $nom;
    var $validation;
    //var $value;
    var $valide;
    var $erreur;
    var $placeholder;

    
    function __construct($nom, $tests = null, $erreur = true, $placeholder = null) {
        global $label;
        parent::__construct($nom);
        $this->validation = array();
        if ($tests != null) {
            foreach ($tests as $test) {
                $this->validation[] = $test;
            }
        }
        $this->erreur = $erreur;
        if ($placeholder == null && isset($label['placeholder_' . $this->nom])) {
            $this->placeholder = $label['placeholder_' . $this->nom];
        }
    }

    function add_test($test) {
        $this->validation[] = $test;
    }

    function valider() {
        $this->erreurs = [];
        $this->valide = true;
        $this->erreur = false;
        if ($this->value !== null) {

            foreach ($this->validation as $test) {
                $err = $test->test($this->value);
                if ($err['erreur']) {
                    $this->valide = false;
                    $this->erreur = true;
                    $this->erreurs[] = $err;
                    return false;
                }
            }
        }
        return true;
    }

    function afficher() {
        global $label;
        ?>
        <div class="control-group">
            <label class="control-label" for="<?php echo $this->nom ?>"><?php echo $label['champ_' . $this->nom] ?></label>
            <div class="controls">
                <input placeholder="<?php echo $this->placeholder ?>" id="<?php echo $this->nom ?>" name="<?php echo $this->nom ?>" type="text" value="<?php if ($this->value !== null) echo htmlentities($this->value); ?>" />
                <?php
                //if ($this->erreur === true) {
                if ($this->erreur) {
                    $this->afficher_erreur();
                }
                ?>
            </div>
        </div>
        <?php
    }

    function afficher_success() {
        global $label;
        ?>
        <span class="text-success"><?php echo $label['msg_success'] ?></span>		
        <?php
    }

    function afficher_erreur() {
        global $label;
        $errString = [];
        foreach ($this->erreurs as $erreur) {
            $str = $label['err_' . $erreur['nom_erreur']];
            if (isset($erreur['remplacements'])) {
                foreach ($erreur['remplacements'] as $remplacement) {
                    $str = preg_replace("/\?\?/", $remplacement, $str, 1);
                }
            }
            $errString[] = $str;
        }
        ?>
        <br>
        <span class="text-error"><?php echo implode("<br>", $errString) ?></span>		
        <?php
    }

}

class PasswordInput extends Input {

    function __construct($nom) {
        parent::__construct($nom);
    }

    function afficher() {
        global $label;
        ?>
        <div class="control-group">
            <label class="control-label" for="<?php echo $this->nom ?>"><?php echo $label['champ_' . $this->nom] ?></label>
            <div class="controls">
                <input id="<?php echo $this->nom ?>" name="<?php echo $this->nom ?>" type="password" />
            </div>
        </div>
        <?php
    }

}

class SelectInput extends Input {

    //var $nom;
    var $options;

    //var $value;

    function __construct($nom) {
        parent::__construct($nom);
        $this->options = array();
    }

    function add_option($text, $value = null) {
        $this->options[] = array($text, $value);
    }

    function setValue($v) {
        $this->value = $v;
    }

    function afficher($erreur = false, $texteErr = "") {
        global $label;
        ?>
        <div class="control-group">
            <label class="control-label" for="<?php echo $this->nom ?>"><?php echo $label['champ_' . $this->nom] ?></label>
            <div class="controls">
                <select id="<?php echo $this->nom ?>" name="<?php echo $this->nom ?>">
                    <?php
                    foreach ($this->options as $option) {
                        if ($option[1] === null) {
                            echo '<option';
                            if ($this->value !== null && $option[0] == $this->value) {
                                echo ' selected="selected"';
                            }
                            echo '>', $option[0], '</option>', "\r\n";
                        } else {
                            echo '<option value="', $option[1], '"';
                            if ($this->value !== null && $option[1] == $this->value) {
                                echo ' selected="selected"';
                            }
                            echo '>', $option[0], '</option>', "\r\n";
                        }
                    }
                    ?>
                </select>
                <?php
                if ($erreur) {
                    echo '<br><span class="text-error">' . $texteErr . '</span>';
                }
                ?>
            </div>
        </div>
        <?php
    }

}

class TextAreaInput extends Input {

    function __construct($nom) {
        parent::__construct($nom);
    }

    function afficher() {
        ?>
        <div class="control-group">
            <label class="control-label"></label>
            <div class="controls">
                <textarea name="<?php echo $this->nom ?>"><?php if ($this->has_value()) echo htmlentities($this->value) ?></textarea>
            </div>
        </div>
        <?php
    }

}