(function($) {
	'use strict';

	const STB_AiDesigner = {
		conversationHistory: [],
		currentDesign: null,
		previousDesign: null,
		uploadedImage: null,
		isGenerating: false,
		showingPrevious: false,
		designVersions: [],

		init() {
			this.cacheElements();
			this.bindEvents();
			this.loadLibrary();
			this.initPreview();
		},

		cacheElements() {
			this.$chatMessages = $('#stb-chat-messages');
			this.$promptInput = $('#stb-prompt-input');
			this.$generateBtn = $('#stb-generate-btn');
			this.$saveBtn = $('#stb-save-btn');
			this.$copyBricksBtn = $('#stb-copy-bricks-btn');
			this.$designTitle = $('#stb-design-title');
			this.$designStatus = $('#stb-design-status');
			this.$tokenInfo = $('#stb-token-info');
			this.$previewIframe = $('#stb-preview-iframe');
			this.$libraryList = $('#stb-library-list');
			this.$imageUpload = $('#stb-image-upload');
			this.$imagePreview = $('#stb-image-preview');
			this.$previewImg = $('#stb-preview-img');
			this.$removeImage = $('#stb-remove-image');
			this.$clearChat = $('#stb-clear-chat');
			this.$refreshLibrary = $('#stb-refresh-library');
			this.$compareBtn = $('#stb-compare-btn');
			this.$feedbackSuggestions = $('#stb-feedback-suggestions');
		},

		bindEvents() {
			this.$generateBtn.on('click', () => this.handleGenerate());
			this.$promptInput.on('keydown', (e) => {
				if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
					this.handleGenerate();
				}
			});
			this.$saveBtn.on('click', () => this.handleSave());
			this.$copyBricksBtn.on('click', () => this.handleCopyToBricks());
			this.$imageUpload.on('change', (e) => this.handleImageUpload(e));
			this.$removeImage.on('click', () => this.removeImage());
			this.$clearChat.on('click', () => this.clearChat());
			this.$refreshLibrary.on('click', () => this.loadLibrary());
			this.$compareBtn.on('click', () => this.toggleCompare());
			this.$feedbackSuggestions.on('click', '.stb-suggestion-chip', (e) => {
				const suggestion = $(e.target).data('suggestion');
				this.$promptInput.val(suggestion).focus();
			});
		},

		initPreview() {
			const iframe = this.$previewIframe[0];
			const doc = iframe.contentDocument || iframe.contentWindow.document;
			doc.open();
			doc.write(`
				<!DOCTYPE html>
				<html>
				<head>
					<meta charset="utf-8">
					<link rel="stylesheet" href="${stbAi.cfCssUrl}">
					<style>
						body { font-family: Inter, system-ui, sans-serif; }
					</style>
				</head>
				<body>
					<div style="padding: 40px; text-align: center; color: #646970;">
						<p>Your design preview will appear here.</p>
					</div>
				</body>
				</html>
			`);
			doc.close();
		},

		async handleGenerate() {
			const prompt = this.$promptInput.val().trim();
			if (!prompt || this.isGenerating) return;

			const isFirstDesign = !this.currentDesign;

			this.isGenerating = true;
			this.$generateBtn.prop('disabled', true).html('<span class="stb-loading"></span> Generating...');
			this.setStatus('generating', isFirstDesign ? 'Generating...' : 'Refining...');

			this.addMessage('user', prompt, this.uploadedImage ? this.uploadedImage.url : null);
			this.$promptInput.val('');

			const images = this.uploadedImage ? [{
				base64: this.uploadedImage.base64,
				mime_type: this.uploadedImage.mime,
				detail: 'high'
			}] : [];

			try {
				let endpoint, params;

				if (isFirstDesign) {
					endpoint = 'stb_ai_generate';
					params = {
						prompt,
						images: JSON.stringify(images),
						history: JSON.stringify(this.conversationHistory)
					};
				} else {
					endpoint = 'stb_ai_refine';
					this.previousDesign = { ...this.currentDesign };
					this.designVersions.push({ ...this.currentDesign });
					params = {
						feedback: prompt,
						history: JSON.stringify(this.conversationHistory),
						images: JSON.stringify(images)
					};
				}

				const response = await this.ajaxRequest(endpoint, params);

				if (response.success) {
					const data = response.data;
					this.currentDesign = data;
					this.conversationHistory.push({ role: 'user', content: prompt });
					this.conversationHistory.push({ role: 'assistant', content: data.description || 'Design updated.' });

					this.addMessage('ai', data.description || (isFirstDesign ? 'Design generated successfully.' : 'Design refined successfully.'));
					this.updatePreview(data.html, data.css);
					this.enableActions();
					this.updateTokenInfo(data.usage);
					this.setStatus('success', isFirstDesign ? 'Generated' : 'Refined');
					this.showFeedbackSuggestions();
					this.updateCompareButton();
				} else {
					this.addMessage('error', response.data || 'Generation failed.');
					this.setStatus('error', 'Failed');
				}
			} catch (error) {
				this.addMessage('error', error.message || 'Request failed.');
				this.setStatus('error', 'Error');
			} finally {
				this.isGenerating = false;
				this.$generateBtn.prop('disabled', false).text(isFirstDesign ? 'Generate' : 'Refine');
				this.removeImage();
			}
		},

		async handleSave() {
			if (!this.currentDesign) return;

			const title = this.$designTitle.val().trim() || 'Untitled Design';
			const designId = this.currentDesign.design_id || 0;

			try {
				const response = await this.ajaxRequest('stb_save_design', {
					title,
					html: this.currentDesign.html,
					css: this.currentDesign.css || '',
					prompt_history: JSON.stringify(this.conversationHistory),
					image_url: this.uploadedImage ? this.uploadedImage.url : '',
					model: this.currentDesign.model || '',
					token_cost: JSON.stringify(this.currentDesign.usage || {}),
					design_id: designId
				});

				if (response.success) {
					this.currentDesign.design_id = response.data.design_id;
					this.currentDesign.version = response.data.version;
					this.addMessage('system', `Design saved (v${response.data.version}).`);
					this.loadLibrary();
				} else {
					this.addMessage('error', response.data || 'Save failed.');
				}
			} catch (error) {
				this.addMessage('error', error.message || 'Save request failed.');
			}
		},

		handleCopyToBricks() {
			if (!this.currentDesign || !this.currentDesign.html) return;

			navigator.clipboard.writeText(this.currentDesign.html).then(() => {
				$('#stb-copy-modal').fadeIn(200);
			}).catch(() => {
				this.addMessage('error', 'Failed to copy to clipboard.');
			});
		},

		async handleImageUpload(e) {
			const file = e.target.files[0];
			if (!file) return;

			if (file.size > stbAi.maxImageSize) {
				this.addMessage('error', 'Image must be less than 5MB.');
				return;
			}

			const formData = new FormData();
			formData.append('action', 'stb_upload_image');
			formData.append('nonce', stbAi.uploadNonce);
			formData.append('image', file);

			try {
				const response = await $.ajax({
					url: stbAi.ajaxUrl,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false
				});

				if (response.success) {
					this.uploadedImage = response.data;
					this.$previewImg.attr('src', response.data.url);
					this.$imagePreview.show();
				} else {
					this.addMessage('error', response.data || 'Upload failed.');
				}
			} catch (error) {
				this.addMessage('error', 'Image upload failed.');
			}

			e.target.value = '';
		},

		removeImage() {
			this.uploadedImage = null;
			this.$imagePreview.hide();
			this.$previewImg.attr('src', '');
			this.$imageUpload.val('');
		},

		clearChat() {
			this.conversationHistory = [];
			this.currentDesign = null;
			this.previousDesign = null;
			this.designVersions = [];
			this.showingPrevious = false;
			this.$chatMessages.empty();
			this.disableActions();
			this.$tokenInfo.text('');
			this.setStatus('ready', 'Ready');
			this.initPreview();
			this.addMessage('system', 'Conversation cleared.');
			this.$compareBtn.hide();
			this.$feedbackSuggestions.empty();
		},

		toggleCompare() {
			if (!this.previousDesign || this.designVersions.length === 0) return;

			if (this.showingPrevious) {
				this.updatePreview(this.currentDesign.html, this.currentDesign.css);
				this.showingPrevious = false;
				this.$compareBtn.text('Show Previous');
				this.setStatus('success', 'Current version');
			} else {
				this.updatePreview(this.previousDesign.html, this.previousDesign.css);
				this.showingPrevious = true;
				this.$compareBtn.text('Show Current');
				this.setStatus('success', 'Previous version');
			}
		},

		updateCompareButton() {
			if (this.previousDesign && this.designVersions.length > 0) {
				this.$compareBtn.show();
			} else {
				this.$compareBtn.hide();
			}
		},

		showFeedbackSuggestions() {
			const suggestions = [
				'Make the button larger',
				'Change primary button to secondary',
				'Add more spacing between sections',
				'Make the title bigger',
				'Add a badge above the heading',
				'Use a card layout for the content',
				'Make it dark mode compatible',
				'Add an icon next to the button',
				'Center align the content',
				'Add a divider between sections'
			];

			const randomSuggestions = suggestions.sort(() => 0.5 - Math.random()).slice(0, 4);
			let html = '<div class="stb-suggestions-row">';
			randomSuggestions.forEach(s => {
				html += `<button type="button" class="stb-suggestion-chip" data-suggestion="${s}">${s}</button>`;
			});
			html += '</div>';

			this.$feedbackSuggestions.html(html);
		},

		async loadLibrary(page = 1) {
			try {
				const response = await this.ajaxRequest('stb_list_designs', { page, per_page: 10 });

				if (response.success && response.data.designs.length > 0) {
					let html = '';
					response.data.designs.forEach(design => {
						html += `
							<div class="stb-library-item" data-id="${design.id}">
								<div class="stb-library-item-header">
									<div class="stb-library-item-title">${this.escapeHtml(design.title)}</div>
									<div class="stb-library-item-actions">
										<button type="button" class="stb-lib-btn stb-lib-load" data-id="${design.id}" title="Load into Designer">Load</button>
										<button type="button" class="stb-lib-btn stb-lib-copy" data-html="${this.escapeAttr(design.html || '')}" title="Copy HTML to Clipboard">Copy</button>
									</div>
								</div>
								<div class="stb-library-item-meta">
									<span>v${design.version || 1}</span>
									<span>${design.model || 'N/A'}</span>
									<span>${design.date}</span>
								</div>
							</div>
						`;
					});
					this.$libraryList.html(html);

					this.$libraryList.find('.stb-lib-load').on('click', async (e) => {
						e.stopPropagation();
						const id = $(e.target).data('id');
						await this.loadDesign(id);
					});

					this.$libraryList.find('.stb-lib-copy').on('click', (e) => {
						e.stopPropagation();
						const html = $(e.target).data('html');
						if (html) {
							navigator.clipboard.writeText(html).then(() => {
								$(e.target).text('Copied!');
								setTimeout(() => $(e.target).text('Copy'), 2000);
							});
						}
					});
				} else {
					this.$libraryList.html('<p class="stb-empty-state">No saved designs yet.</p>');
				}
			} catch (error) {
				this.$libraryList.html('<p class="stb-empty-state">Failed to load library.</p>');
			}
		},

		async loadDesign(designId) {
			try {
				const response = await this.ajaxRequest('stb_load_design', { design_id: designId });

				if (response.success) {
					const data = response.data;
					this.currentDesign = {
						design_id: data.id,
						html: data.html,
						css: data.css,
						model: data.model,
						usage: JSON.parse(data.token_cost || '{}'),
						version: data.version
					};
					this.previousDesign = null;
					this.designVersions = [];
					this.showingPrevious = false;

					this.$designTitle.val(data.title);
					this.conversationHistory = JSON.parse(data.prompt_history || '[]');

					this.updatePreview(data.html, data.css);
					this.enableActions();
					this.updateTokenInfo(this.currentDesign.usage);
					this.setStatus('success', `Loaded v${data.version}`);
					this.updateCompareButton();

					this.$chatMessages.empty();
					if (this.conversationHistory.length > 0) {
						this.conversationHistory.forEach(msg => {
							if (msg.role === 'user') {
								this.addMessage('user', msg.content);
							} else if (msg.role === 'assistant') {
								this.addMessage('ai', msg.content);
							}
						});
					}
				}
			} catch (error) {
				this.addMessage('error', 'Failed to load design.');
			}
		},

		updatePreview(html, css) {
			const iframe = this.$previewIframe[0];
			const doc = iframe.contentDocument || iframe.contentWindow.document;

			let styleBlock = '';
			if (css && css.trim()) {
				styleBlock = `<style>${css}</style>`;
			}

			doc.open();
			doc.write(`
				<!DOCTYPE html>
				<html>
				<head>
					<meta charset="utf-8">
					<link rel="stylesheet" href="${stbAi.cfCssUrl}">
					<style>
						body { font-family: Inter, system-ui, sans-serif; }
					</style>
					${styleBlock}
				</head>
				<body>
					${html}
				</body>
				</html>
			`);
			doc.close();
		},

		addMessage(type, content, imageUrl = null) {
			let html = `<div class="stb-message ${type}">`;
			html += `<div>${this.escapeHtml(content)}</div>`;
			if (imageUrl) {
				html += `<img src="${imageUrl}" alt="Reference" />`;
			}
			html += '</div>';
			this.$chatMessages.append(html);
			this.$chatMessages.scrollTop(this.$chatMessages[0].scrollHeight);
		},

		enableActions() {
			this.$saveBtn.prop('disabled', false);
			this.$copyBricksBtn.prop('disabled', false);
		},

		disableActions() {
			this.$saveBtn.prop('disabled', true);
			this.$copyBricksBtn.prop('disabled', true);
		},

		setStatus(type, text) {
			this.$designStatus.removeClass('generating success error').addClass(type).text(text);
		},

		updateTokenInfo(usage) {
			if (!usage) return;
			const cost = usage.estimated_cost_usd ? `$${usage.estimated_cost_usd.toFixed(6)}` : 'N/A';
			const tokens = usage.total_tokens ? `${usage.total_tokens.toLocaleString()} tokens` : '';
			this.$tokenInfo.text(`${tokens} • ${cost}`);
		},

		ajaxRequest(action, data) {
			return $.ajax({
				url: stbAi.ajaxUrl,
				type: 'POST',
				data: {
					action,
					nonce: stbAi.nonce,
					...data
				}
			}).fail((xhr) => {
				if (xhr.status === 429) {
					this.addMessage('error', 'Rate limit exceeded. Please wait a moment before trying again.');
				} else if (xhr.status === 500) {
					this.addMessage('error', 'Server error. Please try again later.');
				} else if (xhr.responseJSON && xhr.responseJSON.data) {
					this.addMessage('error', xhr.responseJSON.data);
				}
			});
		},

		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},

		escapeAttr(text) {
			return text.replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}
	};

	$(document).ready(() => STB_AiDesigner.init());
})(jQuery);
