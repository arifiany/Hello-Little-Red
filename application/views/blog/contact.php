	<div id="bg" class="content-real">
		<div id="blog">
			<article class="post featured">
			<header>
				<div class="title">
					<h2>Contact Through Email</h2>
					<p>Use this only for really important matter or if you don't want your question to be published. If it's about themes bug or simple question, please <a href="<?=base_url();?>/contact/q">go here instead</a>.</p>
				</div>
			</header>
			
			<?php echo form_open('contact');?> 
			<?php if(validation_errors()){echo validation_errors('<p class="error">','</p>');} ?>
            <?php if($this->session->flashdata('message')){echo '<p class="success">'.$this->session->flashdata('message').'</p>';}?>
	
			<p><label>Name</label>
			<input name="name" placeholder="Your Name" type="text" size="30" /></p>
			
			<p><label>Email</label>
			Please put your real email.
			<input name="email" placeholder="Your Email" type="text" size="30" /></p>
			
			<p><label>Subject</label>
			<input name="subject" placeholder="Subject" type="text" size="30" /></p>

			<p><label>Your Message</label>
			<textarea rows="6" cols="80%" name="message" style="resize:none;" id="textarea"></textarea></p>
			
			<input id="spamer" type="text" name="spamer" size="25" style="display:none" />
			
			
			<input class="button" type="submit" value="Submit"/>
			<input class="button" type="reset" value="Reset"/>	
			</form>	
				
			</article>
		</div>
	<?php $this->load->view('blog/sidebar');?>
</div>
</div>