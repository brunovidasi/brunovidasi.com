package campominado;

import java.awt.event.WindowAdapter;
import java.awt.event.WindowEvent;

import javax.swing.ImageIcon;
import javax.swing.JFrame;
import javax.swing.JOptionPane;

/**
 * @author Bruno Vieira
 * 
 * Mais informações em http://www.brunovidasi.com
 * bruno@brunovidasi.com
 * 
 * Este programa é um jogo clássico denominado "Campo Minado" em que o usuário começa no canto superior esquerdo 
 * e tenta chegar em casa (no canto inferior direito) sem pisar em uma mina. 
 * 
 * O jogo pode ser finalizado clicando na caixa perto da janela, na barra de título.
 * 
 */

// CLASSE PRINCIPAL //
public class Principal {
	
	// CLASSE MAIN //
	public static void main(String[] args) {
		
		// Instancia uma JFrame com o título especificado.
		JFrame janela = new JFrame("Campo Minado - Bruno Vieira");
		CampoMinado board = new CampoMinado();
		
		// DEFINIÇÕES DA TELA //
		janela.setContentPane(board);
		
		// Fazer aparecer o Menu na janela.
		janela.setJMenuBar( new Menu(board) );
		
		// Não deixa o jogador redimensionar a tela.
        janela.setResizable(false);
        
        // Ajustar a janela de acordo com os botões.
		janela.pack();
		
		// Para ao fechar a Janela, não faz nada, para entrar na tela de confirmação de saída.
		janela.setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		
		// Confirmar se deseja sair ou não.
		janela.addWindowListener(new WindowAdapter(){  
            public void windowClosing(WindowEvent e) {  
                int i = JOptionPane.showConfirmDialog(null ,"Tem certeza que deseja sair?", "Saída", JOptionPane.YES_NO_OPTION);  
                if (i == JOptionPane.YES_OPTION) {  
                    System.exit(0);  
                }
            }  
        }); 
		
		// Ícone da Janela
		ImageIcon icone = new ImageIcon("images/icone.png");
	    janela.setIconImage(icone.getImage());
		
		// Colocar a janela no centro da tela.
		janela.setLocationRelativeTo(null);
		
		// Deixar janela visível.
		janela.setVisible(true);
	}

}