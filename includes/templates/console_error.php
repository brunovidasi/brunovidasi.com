<?php
/**
 * The 404's screen: the error the console a lost address names would show
 * (/ps2-… a PS2's, /sims/wiiu-… a Wii U's), as if the game asked for were a
 * disc it could not read. css/lost.css draws them.
 *
 * @var string $console a key of CONSOLES
 */

$name = CONSOLES[$console];
$kind = match ($console) {
    'ps1', 'ps2'                     => 'disc',
    'ps3', 'ps4', 'ps5'              => 'ps',
    'wii', 'wiiu'                    => 'wii',
    'switch', 'switch2', '3ds', 'ds' => 'switch',
    'gb', 'gbc', 'gba', 'snes'       => 'cart',
    'x360'                           => 'rrod',
    'xbox', 'xone'                   => 'xbox',
    default                          => 'bsod',
};
?>
<div class="tv">
  <div class="screen screen-<?= e($kind) ?> screen-<?= e($console) ?>">
<?php if ($kind === 'disc'): ?>
    <span class="disc" aria-hidden="true"></span>
    <p>Please insert a <?= e($name) ?> format disc.</p>
    <small>The disc in the drive is not on the shelf.</small>
<?php elseif ($kind === 'ps'): ?>
    <span class="alert" aria-hidden="true">!</span>
<?php if ($console === 'ps3'): ?>
    <p>An error occurred during the start operation.</p>
    <small>(8002F404)</small>
<?php else: ?>
    <p>An error has occurred.</p>
    <p class="sub">This game is not on the shelf.</p>
    <small>(<?= $console === 'ps5' ? 'CE-108404-0' : 'CE-40404-4' ?>)</small>
<?php endif ?>
<?php elseif ($kind === 'wii'): ?>
    <div class="panel">
<?php if ($console === 'wiiu'): ?>
      <p>Error Code: 150-0404</p>
      <p class="sub">The game could not be found on the shelf. Please check the address and try again.</p>
<?php else: ?>
      <p>The disc could not be read.</p>
      <p class="sub">Refer to the Wii Operations Manual for details.</p>
<?php endif ?>
    </div>
<?php elseif ($kind === 'switch'): ?>
    <div class="panel">
<?php if ($console === 'switch' || $console === 'switch2'): ?>
      <p>The software was closed because an error occurred.</p>
      <small>Error Code: 2404-0404</small>
<?php else: ?>
      <p>An error has occurred.</p>
      <p class="sub">Hold down the POWER Button to turn off the power, then turn it on and try again.</p>
<?php endif ?>
    </div>
<?php elseif ($kind === 'cart'): ?>
    <div class="boot" aria-hidden="true"><span class="logo">Nintendo<sup>®</sup></span></div>
    <p>Blow on the cartridge and try again.</p>
<?php elseif ($kind === 'rrod'): ?>
    <span class="ring" aria-hidden="true"><span></span></span>
    <p>Three red lights.</p>
    <small>Error E404 · this game is not on the shelf</small>
<?php elseif ($kind === 'xbox'): ?>
    <p class="big">Something went wrong.</p>
    <p class="sub">This game is not on the shelf.</p>
    <small>0x80070404</small>
<?php else: ?>
    <div class="bsod">
      <p class="face">:(</p>
      <p>Your shelf ran into a problem and couldn’t find this game.</p>
      <p><span id="bsod-done">0</span>% complete</p>
      <small>Stop code: GAME_NOT_ON_SHELF</small>
    </div>
    <script>
      (() => { const n = document.getElementById('bsod-done'); let d = 0;
        const t = setInterval(() => { d = Math.min(100, d + Math.ceil(Math.random() * 9)); n.textContent = d; if (d === 100) clearInterval(t); }, 260); })();
    </script>
<?php endif ?>
  </div>
</div>
<p class="caption"><?= e($name) ?> · 404</p>
