package campominado;

import java.awt.event.ActionEvent;

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
	
	private CampoMinado campoMinado;
	
    // CONSTRUTOR - CRIANDO BARRA DE MENU //
	public Menu( CampoMinado board ) {
		campoMinado = board;
		JMenu menu = new JMenu("Jogo");
		JMenu record = new JMenu("Record");
		JMenu ajuda = new JMenu("Ajuda");
                
		add(menu);
		add(record);
		add(ajuda);
                
		setupJogoMenu(menu);
		setupAjudaMenu(ajuda);
		setupRecordMenu(record);
	}	
	
	 ////////// MÉTODOS DO MENU //////////
	
    // MENU DE NOVO JOGO //
	private void setupJogoMenu(JMenu menu) {
		
		// Novo jogo na mesma difculdade jogada anteriormente.
		AbstractAction novoJogo =  new AbstractAction("Novo Jogo") {

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				int MinasCont = campoMinado.getMinasCont();
				campoMinado.iniciarJogo(MinasCont);
			}
		};
		
		// Adiciona Atalho para o novo jogo.
		if (System.getProperty("mrj.version") == null)
			novoJogo.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl N"));
		else
			novoJogo.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta N"));
		
		menu.add(novoJogo);
		
		// Separador.
		menu.addSeparator();
		
		// Novo jogo com 30 minas.
		menu.add( new AbstractAction("Novo Jogo - Iniciante"){

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				campoMinado.iniciarJogo(30);
			}
		}); 
		
		// Novo jogo com 40 minas.
		menu.add( new AbstractAction("Novo Jogo - Fácil"){

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				campoMinado.iniciarJogo(40);
			}
		}); 
		
		// Novo jogo com 50 minas.
		menu.add( new AbstractAction("Novo Jogo - Médio"){

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				campoMinado.iniciarJogo(50);
			}
		}); 
		
		// Novo jogo com 60 minas.
		menu.add( new AbstractAction("Novo Jogo - Intermediário"){

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				campoMinado.iniciarJogo(60);
			}
		}); 
		
		// Novo jogo com 75 minas.
		menu.add( new AbstractAction("Novo Jogo - Difícil"){

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				campoMinado.iniciarJogo(75);
			}
		}); 
		
		// Novo jogo com 100 minas.
		menu.add( new AbstractAction("Novo Jogo - Impossível"){

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				campoMinado.iniciarJogo(100);
			}
		}); 
		
		// Separador.
		menu.addSeparator();
		
		// Sair do jogo.
		menu.add( new AbstractAction("Sair"){

			private static final long serialVersionUID = 1L;

			public void actionPerformed(ActionEvent evt) {
				sair();
			}
		}); 
		
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
		
		// Adiciona Atalho para a pontuação.
		if (System.getProperty("mrj.version") == null)
			records.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl R"));
		else
			records.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta R"));
		
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
		
		// Adiciona Atalho para a pontuação.
		if (System.getProperty("mrj.version") == null)
			pontuacao.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("ctrl P"));
		else
			pontuacao.putValue(Action.ACCELERATOR_KEY, KeyStroke.getKeyStroke("meta P"));
		
		record.add(pontuacao);
		
	}
    
    ////////// OUTROS MÉTODOS //////////
	        
    // MANUAL DE INSTRUÇÕES DO JOGO //
    private void manual(){
        String titulo = "Aprenda a Jogar";
        
        String texto = "Controladores:\n\n"
                + "* Botão esquerdo do mouse - Aparece o número de bombas, ou explode.\n"
                + "* Botão direito do mouse  - Marca o quadrado como bomba.\n\n"
                + "Como Jogar:\n\n"
                + "* Tente passsar por todos os quadrados clicando ou marcando como bomba,\n sem estourar nenhuma, "
                + "os quadrados vão te dar as dicas nescessárias \n para continuar o seu caminho.\n\n"
                + "Objetivo:\n\n"
                + "* Chegar ao último quadrado inferior direito marcado de preto :)\n\n"
                + "Boa sorte pequeno gafanhoto!\n\n";
        
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
    	campoMinado.pontuacao(CampoMinado.getPONTOS());
    }
    
    // Sair
    public void sair(){
    	int i = JOptionPane.showConfirmDialog(null ,"Tem certeza que deseja sair?", "Saída", JOptionPane.YES_NO_OPTION);  
        if (i == JOptionPane.YES_OPTION) {  
            System.exit(0);  
        }
	}

}