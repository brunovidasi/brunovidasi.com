package campominado;

import java.awt.Color;
import java.awt.Dimension;
import java.awt.Font;
import java.awt.Graphics;
import java.awt.Image;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;

import javax.swing.ImageIcon;
import javax.swing.JOptionPane;
import javax.swing.JPanel;

/**
 * @author Bruno Vieira
 * 
 * Mais informações em http://www.brunovidasi.com
 * bruno@brunovidasi.com
 * 
 * Todos os Direitos Reservados
 *
 */

public class CampoMinado extends JPanel {
	
	// O Eclipse pediu para adicionar serialVersion
	private static final long serialVersionUID = 1L;
	
	// Definir o número de linhas e de colunas.
	private final static int LINHAS = 15; // LINHAS
	private final static int COLUNAS = 25; // COLUNAS
	
	// Cores personalizadas para o jogo.
	private final static Color AZUL_CLARO   = new Color(0,191,255);
	private final static Color VERDE_CLARO  = new Color(0,255,127);
	
	// Variáveis para a utilização do Array do estado
	private final static int NAO_VISITADO = 0;  
	private final static int COM_BANDEIRA = 1;
	private final static int VISITADO     = 2;
	
	// Pontuação começa zerada.
	private static int PONTOS = 0;
	
	/*
	 * Este ARRAY (estado) contém um dos valores NAO_VISITADO, COM_BANDEIRA ou VISITADO 
	 * para cada campo na placa. O valor inicial é NAO_VISITADO. Se o usuário 
	 * solta uma "bandeira" na praça, indicando que o usuário pensa que há uma bomba lá, 
	 * o estado é alterado para COM_BANDEIRA. Quando o usuário visita um quadrado 
	 * clicando nele (e não ser explodido), o estado é alterado para VISITADO. Esta 
	 * matriz muito importante é usado ao longo desta classe.                            */
	
	private int[][] estado;
	
	// minado[r][c] é verdadeiro se existir uma mina no campo.
	private boolean[][] minado;
	
	// Número de minas no jogo, é definido no início pelo jogador.
	private int qtdMinas;     
	
	private boolean jogoEmAndamento;  // Verdadeiro, se o jogo está em andamento.
	private boolean jogadorVenceu;    // Se jogoEmAndamento for falso, diz se o jogador ganhou
	
	// Cria um jogo inicial com 60 minas
	public CampoMinado() {
		this(60);
	}

	// Cria um jogo que tem 50 minas em posições aleatórias, 
	public CampoMinado(int minaCont) {
		
		// Define as dimensões do jogo.
		setPreferredSize( new Dimension(2 + 24*COLUNAS, 2 + 24*LINHAS ));
		
		// Iniciar o jogo com a quantidade de minas definida.
		
		iniciarJogo(minaCont);
		
		// Adiciona o click do mouse.
		addMouseListener( new MouseAdapter() {
			@Override
			public void mousePressed(MouseEvent evt) {
				doMouseClick(evt);
			}
		});
		
	}
	
	// Retorna o número de minas. Esse número é definido quando um jogo começa, e não muda até que um novo jogo começa.
	public int getMinasCont() {
		return qtdMinas;
	}
	
	// Getter para pegar a pontuação.
	public static int getPONTOS() {
		return PONTOS;
	}
	
	// Setter para setar a pontuação.
	public static void setPONTOS(int Pontos) {
		PONTOS = Pontos;
	}
	
	// Este método processa o evento que ocorre quando o usuário clica nos campos.
	private void doMouseClick(MouseEvent evt) {
		
		// linha e coluna onde o jogador clicou.
		int row = (evt.getY() - 1) / 24;
		int col = (evt.getX() - 1) / 24;
		
		// O Jogador não clicou nos campos.
		if ( row < 0 || row >= LINHAS || col < 0 || col >= COLUNAS)
			return;
		
		// O jogador clicou no campo visitado, não faz mais efeito.
		if (estado[row][col] == VISITADO)
			return;
		
		// O jogador só pode mover-se horizontalmente ou verticalmente a partir de um quadrado já visitou.
		if (! visitarVizinho(row,col))
			return;
		
		// Se o jogo acabou, bloqueia todos os campos.
		if (! jogoEmAndamento)
			return;
		
		// Marcar como bomba (bandeira)
		if (evt.isMetaDown() || evt.isShiftDown()) {
			// Se o usuário clicar com shift ou clicar com o botão direito do mouse, ele vai marcar ou desmarcar como bomba.
			if (estado[row][col] == NAO_VISITADO){
				estado[row][col] = COM_BANDEIRA;
				System.out.println("Campo ["+ row +"]["+ col +"] marcado como bomba.");
				
				// Verifica se o campo marcado com bomba é realmente bomba. Se sim, incrementa 1 ponto.
				if(minado[row][col]){
					setPONTOS(getPONTOS() + 1);
				}
			}
			// O Estado volta a ser NAO_VISITADO se clicar de novo
			else{ 
				estado[row][col] = NAO_VISITADO;
				System.out.println("Campo ["+ row +"]["+ col +"] desmarcado como bomba.");
				
				// Diminui a pontuação, se desmarcou um campo minado como bomba.
				if(minado[row][col]){
					setPONTOS(getPONTOS() - 1);
				}
			}
		}
		else {
			
			// Não deixa o jogador clicar acidentalmente em um campo marcado como bomba.
			if (estado[row][col] == COM_BANDEIRA)
				return; 
			
			// Marcar os quadrados como visitados.
			visitar(row, col);  
		}
		// Redesenhar a placa para mostrar seu status alterado.
		repaint();  
	}
	
	// Desenha o jogo, com base no estado atual de todos os quadrados e sobre se deve ou não o jogo está em andamento. 
	// Este método é chamado pelo sistema e não é para ser chamado diretamente.
	@Override
	protected void paintComponent(Graphics g) {
		// Cor da borda dos quadrados
		g.setColor(Color.GRAY);
		g.fillRect(0, 0, getWidth(), getHeight());
		
		for (int r = 0; r < LINHAS; r++)
			for (int c = 0; c < COLUNAS; c++) {
				
				// Adiciona a Imagem da Bandeira
				//ImageIcon bandeira_ = new ImageIcon("images/flag.png");
				//Image bandeira = bandeira_.getImage();
				
				ImageIcon bomba_ = new ImageIcon("images/bomb.gif");
				Image bomba = bomba_.getImage();
				
				// Define a cor dos campos que tem bomba quando o jogo acaba.
				if (minado[r][c] && !jogoEmAndamento){
					// Se o jogador venceu, ou não, as minas ficam com cores diferentes. 
					if(jogadorVenceu)
						g.setColor(Color.BLUE);
					else{
						g.drawImage(bomba, 6+24*c, 8+24*r, this); 
						g.setColor(Color.RED);
						
					}
				}
					
				// Define a cor dos campos marcados como bomba.
				else if (estado[r][c] == COM_BANDEIRA)
					//g.setColor(Color.WHITE);
					g.setColor(Color.GRAY);
				
				// Define a cor do campo que tem que chegar para encerrar o jogo.
				else if(r == LINHAS-1 && c == COLUNAS-1)
					g.setColor(Color.BLACK);
				
				// Se não tem nenhuma condição, define a cor normal dos campos.
				else
					g.setColor(Color.GRAY);
				
				// Define a cor dos campos visitados de acordo com a cor dos campos escolhida.
				if (estado[r][c] == VISITADO)
					g.fill3DRect( 2+24*c, 2+24*r, 23, 23, false );
				else
					g.fill3DRect( 2+24*c, 2+24*r, 23, 23, true );
				
				// Define a cor inicial dos números dos campos.
				g.setColor(Color.BLACK);
				
				
				
				// Define o que aparece quando o campo é marcado como bomba.
				if (estado[r][c] == COM_BANDEIRA)
					//g.drawString("B", 6+24*c, 15+24*r);
					g.drawImage(bomba, 6+24*c, 8+24*r, this); 
				
				// Define os campos com números de bomba.
				else if (estado[r][c] == VISITADO) {
					int bombas = bombaCont(r,c);
					
					// Escreve nos campos se tiver bombas em volta.
					if (bombas > 0){
						
						// Definindo as cores dos números dos campos visitados.
						if (bombas == 1){
							g.setColor(AZUL_CLARO);
							
						}else if (bombas == 2){
							g.setColor(Color.ORANGE);
							
						}else if (bombas == 3){
							g.setColor(VERDE_CLARO);
							
						}else if (bombas == 4){
							g.setColor(Color.BLUE);
							
						}else if (bombas == 5){
							g.setColor(Color.RED);
							
						}else if (bombas >= 6){
							g.setColor(Color.MAGENTA);
						}
						
						// Definindo a fonte e tamanho dos números de bomba.
						g.setFont(new Font("SERIF", Font.BOLD, 16));
						
						// Escrevendo os números das bombas no campo.
						g.drawString("" + bombas, 6+24*c, 15+24*r);
						
					}
					
					else{
						
						/* 
						 * Desfiz o marcar todos os quadrados pelos motivos de pontuação e da nova regra de negócio que fiz, 
						 * Mas deixei comentado para caso de precisar depois, ou se for nescessário para o trabalho.
						 */
						
						// Para marcar os quadrados vazios em volta
						/*if (r > 0) {
							if (!minado[r-1][c])
								marcar(r-1, c);
						}
						if (c > 0 && !minado[r][c-1])
							marcar(r, c-1);
						if (c < COLS-1 && !minado[r][c+1])
							marcar(r, c+1);
						if (r < ROWS-1) {
							if (!minado[r+1][c])
								marcar(r+1, c);
						}*/
					}
				}
			}
		
		// CONFIGURAÇÕES DO QUE APARECE ESCRITO QUANDO O JOGO ACABA //
		if (!jogoEmAndamento) {
			
			// Define a cor do texto.
			g.setColor(Color.WHITE);
			
			// Define a fonte e tamanho do texto.
			g.setFont(new Font("SERIF", Font.BOLD, 30));
			
			// Define o que está escrito sempre ganhando ou perdendo.
			//g.drawString("O Jogo Acabou", 180, 80);
			
			// Define o que está escrito se perder ou ganhar.
			if (jogadorVenceu){
				g.drawString("PARABÉNS!", 20, 350);
				System.out.println("Jogador venceu!");
			}
			else{
				g.drawString("VOCÊ PERDEU!", 370, 350);
				System.out.println("EXPLODIU.");
			}
			
			// Escreve no Log que o jogo acabou, e o número de pontos.
			System.out.println("Fim do Jogo. "+ getPONTOS() +" pontos.");
			System.out.println("----------------- \n\n");
			
			
		}
		
	}

	// Começa um novo jogo com um número especificado de minas. 
	// Se um jogo está em andamento, o jogo é interrompido e nenhuma mensagem de aviso ou de erro é dado.
	public void iniciarJogo(int qtdMinas) {
		
		// Zera a quantidade de pontos para a nova partida.
		setPONTOS(0);
		
		// Especifica no log do sistema o número de minas que o jogo começou.
		System.out.println("Jogo iniciado com " + qtdMinas + " minas.");
		
		// Escreve o numero de minas na variável;
		this.qtdMinas = qtdMinas;
		
		// O loop termina quando o jogo é criado.
		while (true) { 
			
			jogoEmAndamento = true;
			minado = new boolean[LINHAS][COLUNAS]; // Todos os valores são iniciados falsos.
			estado = new int[LINHAS][COLUNAS];     // Todos os valores são iniciados NAO_VISITADO.
			
			for (int i = 0; i < qtdMinas; i++) { // Coloca uma mina em uma posição aleatória.
				int r,c;
				while (true) {
					r = (int)(LINHAS * Math.random());  // Número de linha sorteado aleatoriamente.
					c = (int)(COLUNAS * Math.random());  // Número de coluna sorteado aleatoriamente.
					if ( (r + c > 2) &&  (r < LINHAS-1 || c < COLUNAS-1) && ! minado[r][c] ) {
					   /*
					    * Terminar o loop se a posição selecionada aleatoriamente é OK, caso contrário, 
					    * tente novamente com uma posição diferente. O teste de "r + c> 2" assegura 
					    * que as posições (0,0), (​​1,0), (​​0,1), (​​1,1), (​​0,2) e (2,0) não são minadas, e 
					    * em particular, que o canto superior esquerdo não é extraído e não tem 
					    * vizinhos que estão minadas. Os outros dois testes garantem que a posição inferior 
					    * direito "home" não é extraído e que não existe já uma mina na praça selecionada.
					    */
						break;
					}
				}
				minado[r][c] = true;
			}
			
			// Se o jogador começou visitando automaticamente o quadrado superior esquerdo.
			visitar(0,0);
			
			// Verifica se o jogo é válido
			if (configuracaoOK()) 
				break;
		}
		repaint();
	}
	
	// O jogador só pode visitar campos que estão ao lado já visitados, ou sinalizados como bomba. 
	// Este método verifica se um determinado campo está ao lado de uma campo visitado.
	private boolean visitarVizinho(int row, int col) {
		if (row > 0 && estado[row-1][col] > NAO_VISITADO)
			return true;
		if (row < LINHAS-1 && estado[row+1][col] > NAO_VISITADO)
			return true;
		if (col > 0 && estado[row][col-1] > NAO_VISITADO)
			return true;
		if (col < COLUNAS-1 && estado[row][col+1] > NAO_VISITADO)
			return true;
		//Se não corresponde a nenhuma condição, retorna falso.
		return false;
	}
	
	// Este método é chamado quando um campo é visitado pelo usuário.
	private void visitar( int row, int col ) {
		// Se o jogador tenha pisa em uma mina, explode.
		if (minado[row][col]) {
			jogoEmAndamento = false;
			jogadorVenceu = false;
			
			// Zera a pontuação, por que perdeu.
			//PONTOS = 0;
		}
		// Se não é mina, marca como VISITADO.
		else {
			// Marca o campo como visitado.
			marcar(row,col);
			
			// Se o jogador visitou o último campo, o jogo termina e ele ganha.
			if (estado[LINHAS - 1][COLUNAS -1] == VISITADO) {
				jogoEmAndamento = false;
				jogadorVenceu = true;
				
				calculaPontos(getPONTOS());
			}
		}
	}
	
	
	// Conta as bombas nos campos de posição vizinho (linha, coluna).
	private int bombaCont(int row, int col) {
		int ct = 0;
		if (row > 0) {
			if (col > 0 && minado[row-1][col-1])
				ct++;
			if (minado[row-1][col])
				ct++;
			if (col < COLUNAS-1 && minado[row-1][col+1])
				ct++;
		}
		if (col > 0 && minado[row][col-1])
			ct++;
		if (col < COLUNAS-1 && minado[row][col+1])
			ct++;
		if (row < LINHAS-1) {
			if (col > 0 && minado[row+1][col-1])
				ct++;
			if (minado[row+1][col])
				ct++;
			if (col < COLUNAS-1 && minado[row+1][col+1])
				ct++;
		}
		return ct;
	}
	
	// Verifica se o jogo está resolvido.
	private boolean configuracaoOK() {
		if ( estado[LINHAS-1][COLUNAS-1] == VISITADO ) {
			System.out.println("Configuração OK.");
			return false;
		}
		return true;
	}
	

	// Marca o campo na posição especificada como visitado.
	private void marcar(int row, int col) {
		estado[row][col] = VISITADO;
		System.out.println("Campo ["+ row +"]["+ col +"] visitado.");
		
		// Incrementa mais um ponto por não ter pisado em uma mina.
		setPONTOS(getPONTOS() + 1);
	}
	
	// Diz a quantidade de pontos obtidos no jogo.
	public void pontuacao(int pontuacao){
		
		// Variáveis para a JOptionPane
		String titulo = "Sua pontuação é:";
		String texto;
		
		// Diferenciar plural no texto da JOptionPane.
		if(pontuacao > 1){
			texto  =  "" + pontuacao + " pontos."; 
			System.out.println("Verificado o número de pontos: "+ pontuacao +" pontos.");
		}else{
			texto  =  "" + pontuacao + " ponto."; 
			System.out.println("Verificado o número de pontos: "+ pontuacao +" ponto.");
		}
		
		// Mensagem de quantos pontos o jogador tem.
		JOptionPane.showMessageDialog(null, texto , titulo, JOptionPane.INFORMATION_MESSAGE);
		
	}
	
	// Calcula os pontos se o jogador venceu.
	private void calculaPontos(int ponto){
		// Adiciona 5 pontos no jogo por ter clicado no campo preto.
		ponto = ponto + 5;
		
		// Adiciona o número de minas usadas no jogo (dificuldade), ao número de pontos.
		ponto = ponto + getMinasCont();
		
		setPONTOS(ponto);
		
		// Exibe para o jogador a pontuação dele.
		pontuacao(ponto);
	}

	
}