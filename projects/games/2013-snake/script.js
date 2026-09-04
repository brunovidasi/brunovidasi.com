// Conversão de Snake.java / Grade.java / Menu.java (Bruno Vieira, 2013)
// para HTML5 Canvas + JavaScript.

(function () {
  "use strict";

  // ---- Constantes (equivalentes às da classe Grade) ----
  const LARGURA = 650;
  const ALTURA = 400;
  const TAMANHO_PONTO = 10;
  const TODOS_PONTOS = (LARGURA * ALTURA) / TAMANHO_PONTO;
  const RAND_POSICAO_X = 64;
  const RAND_POSICAO_Y = 39;

  const canvas = document.getElementById("grade");
  const ctx = canvas.getContext("2d");

  // ---- Imagens ----
  const imgFundo = new Image();
  imgFundo.src = "images/fundo.jpg";

  const imgComida = new Image();
  imgComida.src = "images/food.gif";

  const skins = {
    macho: {
      cabeca: load("images/cabeca.png"),
      corpo: load("images/corpo.png"),
      rabo: load("images/rabo.png"),
    },
    femea: {
      cabeca: load("images/cabeca_femea.png"),
      corpo: load("images/corpo_femea.png"),
      rabo: load("images/rabo_femea.png"),
    },
  };

  function load(src) {
    const img = new Image();
    img.src = src;
    return img;
  }

  let cabeca = skins.macho.cabeca;
  let corpo = skins.macho.corpo;
  let rabo = skins.macho.rabo;

  // ---- Estado do jogo ----
  let DELAY = 150;
  const x = new Array(TODOS_PONTOS).fill(0);
  const y = new Array(TODOS_PONTOS).fill(0);
  let pontos = 3;

  let comida_x = 0;
  let comida_y = 0;

  let PONTUACAO = 0;
  const SCORE_FONT = "bold 13px Consolas, monospace";

  let esquerda = false;
  let direita = false;
  let cima = false;
  let baixo = false;

  let jogoEmAndamento = true;
  let jogoPausado = false;
  let paredesBloqueadas = true;

  let tempo = null; // id do setInterval (equivalente ao javax.swing.Timer)
  let msg = "";

  // ---- Ciclo de jogo ----
  function iniciarJogo() {
    pontos = 3;
    for (let i = 0; i < pontos; i++) {
      x[i] = 50 - i * 10;
      y[i] = 50;
    }

    localComida();

    if (tempo) clearInterval(tempo);
    tempo = setInterval(actionPerformed, DELAY);

    console.log("Novo jogo Iniciado.");
  }

  function iniciarNovoJogo() {
    if (tempo) clearInterval(tempo);

    esquerda = false;
    direita = false;
    cima = false;
    baixo = false;

    PONTUACAO = 0;
    DELAY = 150;
    jogoEmAndamento = true;
    jogoPausado = false;

    iniciarJogo();
  }

  function desenharCobra(cor) {
    const skin = cor === 1 ? skins.femea : skins.macho;
    cabeca = skin.cabeca;
    corpo = skin.corpo;
    rabo = skin.rabo;
    console.log(cor === 1 ? "Snake fêmea escolhida." : "Snake macho escolhida.");
  }

  function localComida() {
    let random = Math.floor(Math.random() * RAND_POSICAO_X);
    comida_x = random * TAMANHO_PONTO;

    random = Math.floor(Math.random() * RAND_POSICAO_Y);
    comida_y = random * TAMANHO_PONTO;
  }

  function checarComida() {
    if (x[0] === comida_x && y[0] === comida_y) {
      pontos++;
      PONTUACAO++;

      const marcos = [9, 12, 15, 18, 20, 22, 25, 30, 32, 35, 40, 45, 50, 60, 70, 80, 90, 100, 150, 200];
      if (marcos.includes(PONTUACAO)) {
        aumentarVelocidade();
      }

      console.log("-> Comeu a fruta.");
      localComida();
    }
  }

  function aumentarVelocidade() {
    DELAY--;
    if (tempo) clearInterval(tempo);
    tempo = setInterval(actionPerformed, DELAY);
    console.log("Velocidade aumentou.");
  }

  function bloquearParede() {
    paredesBloqueadas = true;
    console.log("Paredes Bloqueadas.");
  }

  function desbloquearParede() {
    paredesBloqueadas = false;
    console.log("Paredes Desbloqueadas.");
  }

  function mover() {
    for (let i = pontos; i > 0; i--) {
      x[i] = x[i - 1];
      y[i] = y[i - 1];
    }

    if (esquerda) x[0] -= TAMANHO_PONTO;
    if (direita) x[0] += TAMANHO_PONTO;
    if (cima) y[0] -= TAMANHO_PONTO;
    if (baixo) y[0] += TAMANHO_PONTO;
  }

  function checarColisao() {
    for (let i = pontos; i > 0; i--) {
      if (i > 4 && x[0] === x[i] && y[0] === y[i]) {
        jogoEmAndamento = false;
      }
    }

    if (paredesBloqueadas) {
      if (y[0] > ALTURA) jogoEmAndamento = false;
      if (y[0] < 0) jogoEmAndamento = false;
      if (x[0] > LARGURA) jogoEmAndamento = false;
      if (x[0] < 0) jogoEmAndamento = false;
    } else {
      if (y[0] > ALTURA) y[0] = 0;
      if (y[0] < 0) y[0] = ALTURA;
      if (x[0] > LARGURA) x[0] = 0;
      if (x[0] < 0) x[0] = LARGURA;
    }
  }

  function pausar() {
    jogoPausado = true;
    if (tempo) clearInterval(tempo);
    console.log("Pausado.");
    paint();
  }

  function despausar() {
    jogoPausado = false;
    tempo = setInterval(actionPerformed, DELAY);
    console.log("Despausado.");
  }

  function actionPerformed() {
    if (jogoEmAndamento) {
      checarComida();
      checarColisao();
      mover();
    }
    paint();
  }

  function getPontos() {
    console.log("Pontuação Verificada.");
    return PONTUACAO;
  }

  // ---- Desenho (equivalente ao paint(Graphics)) ----
  function paint() {
    ctx.clearRect(0, 0, LARGURA, ALTURA);

    if (jogoEmAndamento) {
      ctx.drawImage(imgFundo, 0, 0);
      ctx.drawImage(imgComida, comida_x, comida_y);

      for (let i = 0; i < pontos; i++) {
        if (i === 0) {
          ctx.drawImage(cabeca, x[i], y[i]);
        } else {
          ctx.drawImage(corpo, x[i], y[i]);
        }
        if (i === pontos - 1) {
          ctx.drawImage(rabo, x[i], y[i]);
        }
      }

      desenharPontuacao();
      desenharPause();
    } else {
      fimDeJogo();
    }
  }

  function desenharPontuacao() {
    const SCORE = "PONTOS: " + PONTUACAO;
    ctx.fillStyle = "white";
    ctx.font = SCORE_FONT;
    ctx.textBaseline = "alphabetic";
    const w = ctx.measureText(SCORE).width;
    ctx.fillText(SCORE, LARGURA - w - 10, ALTURA - 10);
  }

  function desenharPause() {
    if (!jogoPausado) return;

    ctx.fillStyle = "white";
    ctx.textAlign = "center";

    ctx.font = "bold 40px Consolas, monospace";
    ctx.fillText("PAUSADO", LARGURA / 2, ALTURA / 2);

    ctx.font = "bold 15px Consolas, monospace";
    ctx.fillText("CTRL + R para voltar ao jogo :)", LARGURA / 2, ALTURA / 2 + 25);

    ctx.textAlign = "left";
  }

  function fimDeJogo() {
    ctx.drawImage(imgFundo, 0, 0);

    if (PONTUACAO < 10) {
      msg = "Mais já???? Sua pontuação: " + PONTUACAO;
    } else if (PONTUACAO < 50) {
      msg = "Treine mais srta Snake's! Sua pontuação: " + PONTUACAO;
    } else if (PONTUACAO < 100) {
      msg = "Hmmm... Boa Pontuação! Sua pontuação: " + PONTUACAO;
    } else if (PONTUACAO < 200) {
      msg = "Uiiii, tá ficando bom! Sua pontuação: " + PONTUACAO;
    } else if (PONTUACAO < 250) {
      msg = "Tá fera heim!!! Sua pontuação: " + PONTUACAO;
    } else {
      msg = "Parabéns!! Você é o melhor! Sua pontuação: " + PONTUACAO;
    }

    ctx.fillStyle = "white";
    ctx.font = "bold 14px Consolas, monospace";
    ctx.textAlign = "center";
    ctx.fillText(msg, LARGURA / 2, ALTURA / 2);
    ctx.textAlign = "left";
  }

  // ---- Teclado (equivalente à classe TAdapter) ----
  window.addEventListener("keydown", (e) => {
    switch (e.code) {
      case "ArrowLeft":
        if (!direita) {
          esquerda = true;
          cima = false;
          baixo = false;
          console.log("-> Esquerda");
        }
        e.preventDefault();
        break;
      case "ArrowRight":
        if (!esquerda) {
          direita = true;
          cima = false;
          baixo = false;
          console.log("-> Direita");
        }
        e.preventDefault();
        break;
      case "ArrowUp":
        if (!baixo) {
          cima = true;
          esquerda = false;
          direita = false;
          console.log("-> Cima");
        }
        e.preventDefault();
        break;
      case "ArrowDown":
        if (!cima) {
          baixo = true;
          esquerda = false;
          direita = false;
          console.log("-> Baixo");
        }
        e.preventDefault();
        break;
      case "Escape":
        sair();
        break;
      case "KeyP":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); pausar(); }
        break;
      case "KeyR":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); despausar(); }
        break;
      case "KeyM":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); desenharCobra(0); }
        break;
      case "KeyF":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); desenharCobra(1); }
        break;
      case "KeyH":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); mostrarRecords(); }
        break;
      case "KeyD":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); mostrarPontuacao(); }
        break;
      // Ctrl+N e Ctrl+T são reservados pelo navegador e não podem ser
      // interceptados; use os itens de menu correspondentes.
    }
  });

  // ---- Modal genérico (equivalente ao JOptionPane) ----
  const overlay = document.getElementById("modal-overlay");
  const modalTitle = document.getElementById("modal-title");
  const modalText = document.getElementById("modal-text");
  const modalButtons = document.getElementById("modal-buttons");

  function showMessage(titulo, texto) {
    modalTitle.textContent = titulo;
    modalText.textContent = texto;
    modalButtons.innerHTML = "";
    const ok = document.createElement("button");
    ok.textContent = "OK";
    ok.autofocus = true;
    ok.onclick = closeModal;
    modalButtons.appendChild(ok);
    overlay.classList.remove("hidden");
    ok.focus();
  }

  function showConfirm(titulo, texto, onYes) {
    modalTitle.textContent = titulo;
    modalText.textContent = texto;
    modalButtons.innerHTML = "";

    const yes = document.createElement("button");
    yes.textContent = "Sim";
    yes.onclick = () => { closeModal(); onYes(); };

    const no = document.createElement("button");
    no.textContent = "Não";
    no.onclick = closeModal;

    modalButtons.appendChild(yes);
    modalButtons.appendChild(no);
    overlay.classList.remove("hidden");
    yes.focus();
  }

  function closeModal() {
    overlay.classList.add("hidden");
  }

  // ---- Ações do menu (equivalente à classe Menu) ----
  function manual() {
    showMessage(
      "Aprenda a Jogar",
      "Controladores:\n\n" +
      "* Setas do teclado - CIMA, BAIXO, ESQUERDA e DIREITA.\n\n" +
      "Como Jogar:\n\n" +
      "* Tente comer o máximo de frutas possível e acumular pontos,\n" +
      "enquanto sua cobrinha vai aumentando de tamanho a cada mordida.\n\n" +
      "* Utilize os controladores para movimentar a cobrinha.\n\n" +
      "* Pause o jogo a qualquer momento com CTRL + P\n\n" +
      "Boa sorte pequena snake!"
    );
  }

  function desenvolvedor() {
    showMessage(
      "Sobre o Desenvolvedor:",
      "Bruno Vieira\n\n" +
      "www.brunovidasi.com | bruno@brunovidasi.com\n\n" +
      "ID @brunovidasi 2012.01.74693-1\n\n" +
      "Data de desenvolvimento deste aplicativo: 10/2013\n" +
      "Convertido para HTML/CSS/JS em 2026."
    );
  }

  function mostrarRecords() {
    showMessage("Records", "Banco de Dados ainda não implementado, aguarde a próxima atualização.");
  }

  function mostrarPontuacao() {
    showMessage("Pontuação", "Sua pontuação atual: " + getPontos());
  }

  function sair() {
    showConfirm("Saída", "Tem certeza que deseja sair?", () => {
      if (tempo) clearInterval(tempo);
      showMessage("Jogo encerrado", "Você pode fechar esta aba com segurança.");
    });
  }

  // ---- Ligação dos itens de menu (HTML) ----
  const acoes = {
    novoJogo: iniciarNovoJogo,
    pausar: pausar,
    despausar: despausar,
    sair: sair,
    bloquearParede: bloquearParede,
    desbloquearParede: desbloquearParede,
    cobraMacho: () => desenharCobra(0),
    cobraFemea: () => desenharCobra(1),
    records: mostrarRecords,
    pontuacao: mostrarPontuacao,
    manual: manual,
    desenvolvedor: desenvolvedor,
  };

  document.querySelectorAll("[data-action]").forEach((btn) => {
    btn.addEventListener("click", () => {
      fecharMenus();
      const acao = acoes[btn.dataset.action];
      if (acao) acao();
    });
  });

  // Menus suspensos (equivalente ao comportamento do JMenuBar).
  const menus = document.querySelectorAll(".menu");
  menus.forEach((menu) => {
    const title = menu.querySelector(".menu-title");
    title.addEventListener("click", (e) => {
      e.stopPropagation();
      const wasOpen = menu.classList.contains("open");
      fecharMenus();
      if (!wasOpen) menu.classList.add("open");
    });
  });
  document.addEventListener("click", fecharMenus);

  function fecharMenus() {
    menus.forEach((m) => m.classList.remove("open"));
  }

  // ---- Confirmação ao tentar fechar a aba (equivalente ao WindowListener) ----
  window.addEventListener("beforeunload", (e) => {
    e.preventDefault();
    e.returnValue = "";
  });

  // ---- Inicialização ----
  function start() {
    desenharCobra(0);
    iniciarJogo();
    paint();
  }

  if (imgFundo.complete) {
    start();
  } else {
    imgFundo.onload = start;
  }
})();
