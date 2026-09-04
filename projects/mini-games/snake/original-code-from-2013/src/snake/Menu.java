package snake;

import java.awt.event.ActionEvent;
import java.awt.event.KeyEvent;

import javax.swing.AbstractAction;
import javax.swing.Action;
import javax.swing.JMenu;
import javax.swing.JMenuBar;
import javax.swing.JOptionPane;
import javax.swing.KeyStroke;

/**
 * @author Bruno Vieira
 * 
 * Mais informações em http://www.brunovidasi.com
 * bruno@brunovidasi.com
 * 
 * Classe da Barra de Menu do Jogo
 */

public class Menu extends JMenuBar {
	
	// Eclipse pediu para adicionar serialVersion
	private static final long serialVersionUID = 1L;
	
	private Grade snake;
	
    // CONSTRUTOR - CRIANDO BARRA DE MENU //
	public Menu( Grade board ) {
		snake = board;
		JMenu menu = new JMenu("Jogo");
		JMenu editar = new JMenu("Editar");
		JMenu record = new JMenu("Record");
		JMenu ajuda = new JMenu("Ajuda");
                
		add(menu);
		add(editar);
		add(record);
		add(ajuda);
                
		setupJogoMenu(menu);
		setupEditarMenu(editar);
		setupAjudaMenu(ajuda);
		setupRecordMenu(record);
	}	
	
	 ////////// MÉTODOS DO MENU //////////
	
    // MENU DE NOVO JOGO //
	private void setupJogoMenu(JMenu menu) {
		
		
		// Novo jogo.
		AbstractAction novoJogo =  new AbstractAction("Novo Jogo") {

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				snake.iniciarNovoJogo();
			}
		};
		
		if (System.getProperty("mrj.version") == null)
			novoJogo.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl N"));
		else
			novoJogo.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta N"));
		
		menu.add(novoJogo);
		
		
		// Separador.
		menu.addSeparator();
		
		
		// Pausar o jogo.
		AbstractAction pausar =  new AbstractAction("Pausar Jogo") {

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				snake.pausar();
			}
		};
		
		if (System.getProperty("mrj.version") == null)
			pausar.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl P"));
		else
			pausar.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta P"));
		
		menu.add(pausar);
		
		
		
		// Retornar o jogo.		
		AbstractAction despausar =  new AbstractAction("Retornar Jogo") {

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				snake.despausar();
			}
		};
		
		if (System.getProperty("mrj.version") == null)
			despausar.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl R"));
		else
			despausar.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta R"));
		
		menu.add(despausar);
		
		
		
		// Separador.
		menu.addSeparator();
		
		
		
		// Sair do jogo.		
		AbstractAction sair =  new AbstractAction("Sair") {

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				sair();
			}
		};
		
		if (System.getProperty("mrj.version") == null)
			sair.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke(KeyEvent.VK_ESCAPE, 0));
		else
			sair.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke(KeyEvent.VK_ESCAPE, 0));
		
		menu.add(sair);
		
	}
	
	
	// MENU DE EDITAR JOGO //
	private void setupEditarMenu(JMenu editar) {
			
			
		// Bloquear Paredes.
		AbstractAction bloquear =  new AbstractAction("Bloquear Paredes") {

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				snake.bloquearParede();
			}
		};
		
		if (System.getProperty("mrj.version") == null)
			bloquear.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl T"));
		else
			bloquear.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta T"));
		
		editar.add(bloquear);
		
		
		// Desbloquear Paredes.
		AbstractAction desbloquear =  new AbstractAction("Desbloquear Paredes") {

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				snake.desbloquearParede();
			}
		};
		
		if (System.getProperty("mrj.version") == null)
			desbloquear.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl Y"));
		else
			desbloquear.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta Y"));
		
		editar.add(desbloquear);
		
		
		// Separador.
		editar.addSeparator();
		
		// Trocar para cobra macho.
		AbstractAction macho =  new AbstractAction("Cobra Macho") {

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				snake.desenharCobra(0);
			}
		};
		
		if (System.getProperty("mrj.version") == null)
			macho.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl M"));
		else
			macho.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta M"));
		
		editar.add(macho);
		
		// Trocar para cobra macho.
		AbstractAction femea =  new AbstractAction("Cobra Fêmea") {

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				snake.desenharCobra(1);
			}
		};
		
		if (System.getProperty("mrj.version") == null)
			femea.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl F"));
		else
			femea.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta F"));
		
		editar.add(femea);
		
	}
        
	
	
  	// MENU DE AJUDA //
	private void setupAjudaMenu(JMenu ajuda) {
		
		
		// Manual do jogo.
		ajuda.add( new AbstractAction("Manual"){

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				manual();
			}
		}); 
            
		
		
		// Separador.
        ajuda.addSeparator();
        
        
        
        // Sobre o desenvolvedor.
		ajuda.add( new AbstractAction("Sobre o desenvolvedor"){
			
			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				desenvolvedor();
			}
		}); 
		
	}
	
	
	// MENU DE RECORD //
    private void setupRecordMenu(JMenu record) {
    	
    	
    	// Records.
		AbstractAction records =  new AbstractAction("Records") {

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				record();
			}
		};
		
		if (System.getProperty("mrj.version") == null)
			records.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl H"));
		else
			records.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta H"));
		
		record.add(records);
		
		
		
		// Separador.
		record.addSeparator();
        
		
		
		// Pontuação.
		AbstractAction pontuacao =  new AbstractAction("Ver Pontos Atuais") {

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				pontuacao();
			}
		};
		

		if (System.getProperty("mrj.version") == null)
			pontuacao.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl D"));
		else
			pontuacao.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta D"));
		
		record.add(pontuacao);
		
	}
    
    
    ////////// OUTROS MÉTODOS //////////
	        
    // MANUAL DE INSTRUÇÕES DO JOGO //
    private void manual(){
        String titulo = "Aprenda a Jogar";
        
        String texto = "Controladores:\n\n"
                + "* Setas do teclado - CIMA, BAIXO, ESQUERDA e DIREITA.\n\n"
                + "Como Jogar:\n\n"
                + "* Tente comer o máximo de frutas possivel e acumular pontos,\n "
                + "enquanto sua cobrinha vai aumentando de tamanho a cada mordida.\n\n "
                + "* Utilize os controladores para movimentar a cobrinha.\n\n"
                + "* Pause o jogo a qualquer momento com CTRL + P\n\n"
                + "Boa sorte pequena snake!\n\n";
        
        JOptionPane.showMessageDialog(null, texto , titulo, JOptionPane.INFORMATION_MESSAGE);
        
    }
    
    // SOBRE O DESENVOLVEDOR //
    private void desenvolvedor(){
        String titulo = "Sobre o Desenvolvedor:";
        
        String texto = "Bruno Vieira\n\n"
                + "www.brunovidasi.com | bruno@brunovidasi.com\n\n"
                + "ID @brunovidasi 2012.01.74693-1\n\n"
                + "Data de desenvolvimento deste aplicativo: 10/2013";
        
        JOptionPane.showMessageDialog(null, texto , titulo, JOptionPane.INFORMATION_MESSAGE);
        
    }
    
    // RECORD //
    private void record(){
        String titulo = "Records";
        
        //String texto = "Atualmente não existe nenhum record disponível.";
        String texto = "Banco de Dados ainda não implementado, aguarde a próxima atualização.";
        
        JOptionPane.showMessageDialog(null, texto , titulo, JOptionPane.ERROR_MESSAGE);
        
    }
    
    // PONTUAÇÃO //
    private void pontuacao(){
    	String titulo = "Pontuação";
        
        //String texto = "Atualmente não existe nenhum record disponível.";
        String texto = "Sua pontuação atual: " + snake.getPontos();
        
        JOptionPane.showMessageDialog(null, texto , titulo, JOptionPane.INFORMATION_MESSAGE);
    }
    
    // SAIR //
    public void sair(){
    	int i = JOptionPane.showConfirmDialog(null ,"Tem certeza que deseja sair?", "Saída", JOptionPane.YES_NO_OPTION);  
        if (i == JOptionPane.YES_OPTION) {  
            System.exit(0);  
        }
	}

}