package snake;

import java.awt.Color;
import java.awt.Font;
import java.awt.FontMetrics;
import java.awt.Graphics;
import java.awt.Image;
import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.KeyAdapter;
import java.awt.event.KeyEvent;

import javax.swing.ImageIcon;
import javax.swing.JPanel;
import javax.swing.Timer;

/**
 *
 * @author Bruno Vieira
 * Data de Desenvolvimento: 03/10/2013
 *
 */

public class Grade extends JPanel implements ActionListener{

    // Eclipse pediu para adicionar Serial.
	private static final long serialVersionUID = 1L;
	
	// Tamanho do JPanel em Largura x Altura.
    private final int LARGURA = 650;
    private final int ALTURA  = 400;

    // Tamanho de cada ponto na tela.
    private final int TAMANHO_PONTO = 10;
    
    // Tamanho total de pontos, multiplicando a largura e altura.
    private final int TODOS_PONTOS = (LARGURA * ALTURA)/TAMANHO_PONTO;

    // Um valor para gerar posição da comida.
    private final int RAND_POSICAO_X = 64;
    private final int RAND_POSICAO_Y = 39;
    
    // Um delay para o tempo de execução do jogo.
    private int DELAY = 150;

    // Definição do plano cartesiano (x,y) do jogo.
    private int[] x = new int[TODOS_PONTOS];
    private int[] y = new int[TODOS_PONTOS];

    // Pontos da cobrinha.
    private int pontos;
    
    // Posição (x,y) da comida.
    private int comida_x;
    private int comida_y;
    
    // String usada no fim do jogo para a mensagem.
    public String msg;

    // Contar pontuação.
    private int PONTUACAO = 0;
    
    // Mensagem da pontuação.
    String SCORE = "PONTUAÇÃO: " + PONTUACAO;
    
    // Fonte para escrever a pontuação, estilo da fonte.
    Font SCORE_FONT = new Font("Consolas", Font.BOLD, 12);
    
    // Tamanho total da escrita na tela.
    FontMetrics SCORE_METRICA = this.getFontMetrics(SCORE_FONT);

    // Definição dos movimentos.
    private boolean esquerda = false;
    private boolean direita  = false;
    private boolean cima 	 = false;
    private boolean baixo 	 = false;

    // Denifição do status do jogo.
    private boolean jogoEmAndamento   = true;
    private boolean jogoPausado 	  = false;
    private boolean paredesBloqueadas = true;

    // Tempo de execução do jogo.
    private Timer tempo;

    // Imagens da cabeça, corpo e rabo da cobrinha, comida e background.
    private Image corpo;
    private Image comida;
    private Image cabeca;
    private Image rabo;
    private Image fundo;

    

    // Método construtor da classe.
    public Grade (){
    	
        // Cria uma instrução de teclado.
        addKeyListener(new TAdapter());

        // Seta o plano de fundo como preto.
        setBackground(Color.BLACK);
        
        // Define a imagem de plano de fundo..
        fundo = Toolkit.getDefaultToolkit().createImage("images/fundo.jpg");
        
        desenharCobra(0);

        // Define o foco para o JPanel.
        setFocusable(true);
        
        // Define o tamanho da tela.
        setSize(LARGURA, ALTURA);

        // Inicializa do jogo.
        iniciarJogo();
    }
    
    public void desenharCobra(int cor){
    	
    	// Cria um icone do arquivo png e seta na imagem correspondente da comida.
        ImageIcon comida_ = new ImageIcon("images/food.gif");
        comida = comida_.getImage();
        
        // Imagens para Cobra Macho.
        ImageIcon cabeca_macho = new ImageIcon("images/cabeca.png");
        ImageIcon bola_macho = new ImageIcon("images/corpo.png");
        ImageIcon rabo_macho = new ImageIcon("images/rabo.png");
        
        // Imagens para Cobra Fêmea.
        ImageIcon cabeca_femea = new ImageIcon("images/cabeca_femea.png");
        ImageIcon bola_femea = new ImageIcon("images/corpo_femea.png");
        ImageIcon rabo_femea = new ImageIcon("images/rabo_femea.png");
        
        // Imagens para Cobra Invisivel.
        ImageIcon cabeca_vermelha = new ImageIcon("images/cabeca_invisivel.png");
        ImageIcon bola_vermelha = new ImageIcon("images/bola_invisivel.png");
        ImageIcon rabo_vermelho = new ImageIcon("images/rabo_invisivel.png");
        
        // Imagens para Cobra Preta.
        ImageIcon cabeca_preta = new ImageIcon("images/cabeca_preta.png");
        ImageIcon bola_preta = new ImageIcon("images/bola_preta.png");
        ImageIcon rabo_preto = new ImageIcon("images/rabo_preto.png");
        
        // Se for macho.
        if(cor == 0){
	        corpo = bola_macho.getImage();
	        cabeca = cabeca_macho.getImage();
	        rabo = rabo_macho.getImage();
	        System.out.println("Snake macho escolhida.");
    	}
        
        // Se for fêmea.
        else if(cor == 1){
        	corpo = bola_femea.getImage();
	        cabeca = cabeca_femea.getImage();
	        rabo = rabo_femea.getImage();
	        System.out.println("Snake fêmea escolhida.");
    	}
        
        // Se for vermelha.
        else if(cor == 2){
        	corpo = bola_vermelha.getImage();
	        cabeca = cabeca_vermelha.getImage();
	        rabo = rabo_vermelho.getImage();
	        System.out.println("Snake vermelha escolhida.");
    	}
        
        // Se for preta.
        else if(cor == 3){
        	corpo = bola_preta.getImage();
	        cabeca = cabeca_preta.getImage();
	        rabo = rabo_preto.getImage();
	        System.out.println("Snake preta escolhida.");
    	}
    }

    // Método para inicializar o jogo.
    public void iniciarJogo(){
    	
        // Define o comprimento da cobrinha inicial.
        pontos = 3;

        // Define a posição em (x,y) de cada ponto.
        for (int i = 0; i < pontos; i++)
        {
            x[i] = 50 - i*10;
            y[i] = 50;
        }

        // Gera a primeira comida.
        localComida();

        // Inicia o tempo de execução do jogo.
        tempo = new Timer(DELAY, this);
        tempo.start();
        
        System.out.println("Novo jogo Iniciado.");
    }
    
 // Método para inicializar novo jogo.
    public void iniciarNovoJogo(){
    	tempo.stop();
    	
    	esquerda = false;
        direita  = false;
        cima 	 = false;
        baixo 	 = false;
        
        PONTUACAO = 0;
        
        DELAY = 150;
        
        jogoEmAndamento = true;
        
        iniciarJogo();
    }

    // Método para desenhar elementos na tela do jogo.
    @Override
    public void paint (Graphics g){
    	
        // Define o atribuito para a classe própria
        super.paint(g);
        
        
        // Analisa se o jogo esta em andamento, se estiver desenha na tela, se não estiver, o jogo é dado como o fim.
        if (jogoEmAndamento){
        	
        	// Define o background do jogo.
        	g.drawImage(fundo, 0, 0, null);
        	
            // Desenha a comida no plano (x,y) do jogo.
            g.drawImage(comida, comida_x, comida_y, this);

            // Para cada ponto da cobrinha, desenha a cabeça, corpo e rabo em (x,y).
            for (int i = 0; i < pontos; i++)
            {
                if (i == 0){ 
                	g.drawImage(cabeca, x[i], y[i], this); 
                }
                else{ 
                	g.drawImage(corpo, x[i], y[i], this); 
                }
                
                if (i == pontos-1){ 
                	g.drawImage(rabo, x[i], y[i], this); 
                }
            }
            
            // Desenha a pontuação na tela.
            desenharPontuacao(g);
            
            // Desenha se o jogo estiver pausado.
            desenharPause(g);
            
            // Executa a sincronia de dados.
            Toolkit.getDefaultToolkit().sync();

            // Pausa os gráficos.
            g.dispose();
            
        }        
        // Executa o fim de jogo.
        else{
        	System.out.println("Fim do jogo.");
            FimDeJogo(g);
        }
    }

    // Método para desenhar a pontuação na tela.
    public void desenharPontuacao (Graphics g){

    	// Define a frase para escrever.
        SCORE = "PONTOS: " + PONTUACAO;
        
        String VELOCIDADE = "" + DELAY;
        
        // Define o tamanho da fonte.
        SCORE_METRICA = this.getFontMetrics(SCORE_FONT);

        // Define a cor da fonte.
        g.setColor(Color.white);
        
        // Seta a fonte para o gráfico.
        g.setFont(SCORE_FONT);
       
        // Desenha a fonte na tela.
        g.drawString(SCORE, (LARGURA - SCORE_METRICA.stringWidth(SCORE)) - 10, ALTURA - 10);
        
        g.drawString(VELOCIDADE, (LARGURA - SCORE_METRICA.stringWidth(VELOCIDADE)) - 610, ALTURA - 10);
        
    }
    
    public void desenharPause(Graphics g){
    	if(jogoPausado){
        	
        	// Define o estilo da fonte.
            Font grande = new Font("Consolas", Font.BOLD, 40);
            Font pequeno = new Font("Consolas", Font.BOLD, 15);
            
            // Define o tamanho da fonte
            FontMetrics metrica_grande = this.getFontMetrics(grande);
            FontMetrics metrica_pequeno = this.getFontMetrics(pequeno);

            // Define a cor da fonte.
            g.setColor(Color.white);
            
            // Seta a fonte para o gráfico.
            g.setFont(grande);
            
            // Desenha a fonte na tela.
            g.drawString("PAUSADO", (LARGURA - metrica_grande.stringWidth("PAUSADO")) / 2, ALTURA / 2);
            
            g.setFont(pequeno);
            
            g.drawString("CTRL + R para voltar ao jogo :)", (LARGURA - metrica_pequeno.stringWidth("CTRL + R para voltar ao jogo :)")) / 2, (ALTURA / 2) + 25);
            
        }
    }
    
    // Método que aparece quando o jogo acaba.
    public void FimDeJogo (Graphics g){
    	
    	// Define a imagem de fundo (background).
    	g.drawImage(fundo, 0, 0, null);
    	
        // Define a frase para escrever.
        if(PONTUACAO < 10){
            msg = "Mais já???? Sua pontuação: " + PONTUACAO;
        }else if(PONTUACAO < 50){
            msg = "Treine mais srta Snake's! Sua pontuação: " + PONTUACAO;
        }else if(PONTUACAO < 100){
            msg = "Hmmm... Boa Pontuação! Sua pontuação: " + PONTUACAO;
        }else if(PONTUACAO < 200){
            msg = "Uiiii, tá ficando bom! Sua pontuação: " + PONTUACAO;
        }else if(PONTUACAO < 250){
            msg = "Tá fera heim!!! Sua pontuação: " + PONTUACAO;
        }else if(PONTUACAO >= 250){
            msg = "Parabéns!! Você é o melhor! Sua pontuação: " + PONTUACAO;
        }
        
        // Define o estilo da fonte.
        Font pequena = new Font("Consolas", Font.BOLD, 14);
        
        // Define o tamanho da fonte
        FontMetrics metrica = this.getFontMetrics(pequena);

        // Define a cor da fonte.
        g.setColor(Color.white);
        
        // Seta a fonte para o gráfico.
        g.setFont(pequena);
        
        // Desenha a fonte na tela.
        g.drawString(msg, (LARGURA - metrica.stringWidth(msg)) / 2, ALTURA / 2);
    }

    // Método para checar se a cobrinha comeu a comida.
    public void checarComida (){
        
    	// Se a cobrinha comer na mesma posição (x,y).
        if ((x[0] == comida_x) && (y[0] == comida_y))
        {
        	// Aumenta o corpo da cobrinha
            pontos++;
            
            // Aumenta a pontuação
            PONTUACAO++;
            
            // Aumenta a velocidade da cobrinha em casos específicos.
            if(PONTUACAO == 9){
            	aumentarVelocidade();
            }else if (PONTUACAO == 12){
            	aumentarVelocidade();
            }else if (PONTUACAO == 15){
            	aumentarVelocidade();
            }else if (PONTUACAO == 18){
            	aumentarVelocidade();
            }else if (PONTUACAO == 20){
            	aumentarVelocidade();
            }else if (PONTUACAO == 22){
            	aumentarVelocidade();
            }else if (PONTUACAO == 25){
            	aumentarVelocidade();
            }else if (PONTUACAO == 30){
            	aumentarVelocidade();
            }else if (PONTUACAO == 32){
            	aumentarVelocidade();
            }else if (PONTUACAO == 35){
            	aumentarVelocidade();
            }else if (PONTUACAO == 40){
            	aumentarVelocidade();
            }else if (PONTUACAO == 45){
            	aumentarVelocidade();
            }else if (PONTUACAO == 50){
            	aumentarVelocidade();
            }else if (PONTUACAO == 60){
            	aumentarVelocidade();
            }else if (PONTUACAO == 70){
            	aumentarVelocidade();
            }else if (PONTUACAO == 80){
            	aumentarVelocidade();
            }else if (PONTUACAO == 90){
            	aumentarVelocidade();
            }else if (PONTUACAO == 100){
            	aumentarVelocidade();
            }else if (PONTUACAO == 150){
            	aumentarVelocidade();
            }else if (PONTUACAO == 200){
            	aumentarVelocidade();
            }
            
            System.out.println("-> Comeu a fruta.");
            
            // Gera comida em outro lugar.
            localComida();
        }
    }
    
    // Método para aumentar a velocidade da cobrinha.
    public void aumentarVelocidade (){
    	// Diminui do tempo de execução do jogo.
    	DELAY--;
        
    	tempo.stop();
    	
        tempo = new Timer(DELAY, this);
        tempo.start();
        
        System.out.println("Velocidade aumentou.");
    }
    
    // Método para bloquear as paredes
    public void bloquearParede(){
    	paredesBloqueadas = true;
    	System.out.println("Paredes Bloqueadas.");
    }
    
    // Método para desbloquear as paredes
    public void desbloquearParede(){
    	paredesBloqueadas = false;
    	System.out.println("Paredes Desbloqueadas.");
    }

    // Método para mover a cobrinha na tela.
    public void mover (){
        // Para cada ponto da cobrinha desenha em (x,y).
        for (int i = pontos; i > 0; i--){
            x[i] = x[(i - 1)];
            y[i] = y[(i - 1)];
        }

        // Se for para esquerda decrementa em x.
        if (esquerda){
            x[0] -= TAMANHO_PONTO;
        }

        // Se for para direita incrementa em x.
        if (direita){
            x[0] += TAMANHO_PONTO;
        }

        // Se for para cima decrementa em y.
        if (cima){
            y[0] -= TAMANHO_PONTO;
        }

        // Se for para baixo incrementa em y.
        if (baixo){
            y[0] += TAMANHO_PONTO;
        }
        
    }

    // Método para checar colisão entre a cobrinha e as bordas do jogo.
    public void checarColisao (){
    	
        // Para cada ponto, verifica se este está em posição com outro ponto. Se estiver ele para o jogo.
        for (int i = pontos; i > 0; i--){
            if ((i > 4) && (x[0] == x[i]) && (y[0] == y[i])){ 
            	jogoEmAndamento = false; 
            }
        }
        
        // Verifica se a cabeça da cobrinha encostou em algum ponto (x,y) nas bordas (width,height) da tela.
	    if(paredesBloqueadas){   
	    	if (y[0] > ALTURA){ 
	        	jogoEmAndamento = false; 
	        }
	
	        if (y[0] < 0){ 
	        	jogoEmAndamento = false; 
	        }
	
	        if (x[0] > LARGURA){ 
	        	jogoEmAndamento = false; 
	        }
	
	        if (x[0] < 0){ 
	        	jogoEmAndamento = false; 
	        }
	    }else{
	    	if (y[0] > ALTURA){ 
	        	y[0] = 0;
	        }
	
	        if (y[0] < 0){ 
	        	y[0] = ALTURA;
	        }
	
	        if (x[0] > LARGURA){ 
	        	x[0] = 0;
	        }
	
	        if (x[0] < 0){ 
	        	x[0] = LARGURA;
	        }
	    }
    }

    // Método que gera uma comida na tela.
    public void localComida (){
        // Define um valor aleatório e atribui a uma posição x na tela para a comida.
        int random = (int) (Math.random() * RAND_POSICAO_X);
        comida_x = (random * TAMANHO_PONTO);

        // Define um valor aleatório e atribui a uma posição y na tela para a comida.
        random = (int) (Math.random() * RAND_POSICAO_Y);
        comida_y = (random * TAMANHO_PONTO);
    }
    
    // Método chamado para pausar o jogo.
    public void pausar(){
    	jogoPausado = true;
    	tempo.stop();
    	System.out.println("Pausado.");
    	repaint();
    }
    
    // Método chamado para despausar o jogo.
    public void despausar(){
    	jogoPausado = false;
    	tempo.start();
    	System.out.println("Despausado.");
    }
    

    // Método de ações durante a execução do jogo.
    public void actionPerformed (ActionEvent e){
        if (jogoEmAndamento){
            checarComida();
            checarColisao();
            mover();
        }
        
        repaint();
    }
    
    // Método para retornar a pontuação.
    public int getPontos(){
    	System.out.println("Pontuação Verificada.");
		return PONTUACAO;
    }

    // Classe para analisar o teclado.
    private class TAdapter extends KeyAdapter{

        // Método para verificar o que foi teclado.
        @Override
        public void keyPressed (KeyEvent e)
        {
            // Obtém o código da tecla.
            int key =  e.getKeyCode();

            // Verifica os movimentos e manipula as variáveis, para movimentar corretamente sobre a tela.
            if ((key == KeyEvent.VK_LEFT) && (!direita)){
                esquerda = true;
                cima = false;
                baixo = false;
                System.out.println("-> Esquerda");
            }

            if ((key == KeyEvent.VK_RIGHT) && (!esquerda)){
                direita = true;
                cima = false;
                baixo = false;
                System.out.println("-> Direita");
            }

            if ((key == KeyEvent.VK_UP) && (!baixo)){
                cima = true;
                esquerda = false;
                direita = false;
                System.out.println("-> Cima");
            }

            if ((key == KeyEvent.VK_DOWN) && (!cima)){
                baixo = true;
                esquerda = false;
                direita = false;
                System.out.println("-> Baixo");
            }
        }
    }
 
}
