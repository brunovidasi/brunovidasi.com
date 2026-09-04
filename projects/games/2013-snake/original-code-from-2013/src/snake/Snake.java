package snake;

import java.awt.event.WindowAdapter;
import java.awt.event.WindowEvent;

import javax.swing.ImageIcon;
import javax.swing.JFrame;
import javax.swing.JOptionPane;

import snake.Menu;

/**
*
* @author Bruno Vieira
* Data de Desenvolvimento: 03/10/2013
* 
*/

public class Snake extends JFrame
{
    
    // Eclipse pediu para adicionar serial
	private static final long serialVersionUID = 1L;

	public Snake (){
        // Adicionando e instanciando a Grade.
		add(new Grade());
        Grade board = new Grade();
        
        
        setContentPane(board);
        
        // Fazendo aparecer o JMenu.
        setJMenuBar( new Menu(board) );
     		
     	// Para ao fechar a Janela, não faz nada, para entrar na tela de confirmação de saída.
     	setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
     		
     	// Confirmar se deseja sair ou não.
     	addWindowListener(new WindowAdapter(){  
	        public void windowClosing(WindowEvent e) {  
	            int i = JOptionPane.showConfirmDialog(null,"Tem certeza que deseja sair?", "Saída", JOptionPane.YES_NO_OPTION);  
	            if (i == JOptionPane.YES_OPTION) {  
	                System.exit(0);  
	            }
	        }  
	    }); 
     	
     	// Definindo as dimensões do aplicativo.
        setSize(670, 460);
        
        // Definindo do jogo aparecer no centro da tela.
        setLocationRelativeTo(null);
        
        // Definindo título da janela.
        setTitle("Blue Snake Revolution!");
        
        // Bloqueando o redimensionamento da janela.
        setResizable(false);
        
        // Ícone da Janela
 		ImageIcon icone = new ImageIcon("images/cabeca.png");
 	    setIconImage(icone.getImage());
     	    
        // Deixando a janela visível.
        setVisible(true);
    }

    public static void main(String[] args) 
    {
        new Snake();
    }

}