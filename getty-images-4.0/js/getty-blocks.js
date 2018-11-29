// Don't run this outside of Gutenberg
if (wp.blocks) {

const { registerBlockType } = wp.blocks;
const { MediaUpload } = wp.editor;
const el = wp.element.createElement;

const gettyLogo = el("svg",
	{ viewBox: "0 0 98 97", version: "1.1" },
	el("g",
		{ transform: "matrix(1,0,0,1,-1.32872e-06,2.32748e-06)" },
		el("ellipse", { cx: "49", cy: "48.5", rx: "48.75", ry: "48.75", style: { fill: "white" }})
	),
	el("path", 
		{ d: "M49,-0.25C75.906,-0.25 97.75,21.594 97.75,48.5C97.75,75.406 75.906,97.25 49,97.25C22.094,97.25 0.25,75.406 0.25,48.5C0.25,21.594 22.094,-0.25 49,-0.25ZM67.022,22.86C62.555,22.717 59.601,23.857 57.784,26.137C55.361,24.142 51.424,22.86 48.092,22.86C37.868,22.86 32.568,29.271 32.568,35.754C32.568,40.243 35.143,43.092 38.399,45.586C36.658,46.085 32.265,48.791 32.265,52.353C32.265,56.343 36.278,57.269 38.096,58.337L38.096,58.48C34.688,58.48 30.978,60.688 30.978,65.32C30.978,71.161 37.944,74.152 48.546,74.152C61.116,74.152 66.796,68.454 66.796,63.182C66.796,48.364 40.898,56.486 40.898,50.501C40.898,48.364 44.154,48.221 48.242,48.221C58.086,48.221 63.084,43.234 63.084,36.253C63.084,33.404 62.175,31.266 60.965,29.699C62.403,28.844 64.826,28.844 67.022,28.844L67.022,22.86ZM46.274,59.904C55.361,59.904 58.162,62.469 58.162,64.179C58.162,65.461 56.118,68.311 50.06,68.311C42.185,68.311 39.61,67.028 39.61,64.179C39.61,62.612 41.125,59.904 46.274,59.904ZM48.016,41.667C44.003,41.667 41.2,38.961 41.2,35.54C41.2,32.12 44.003,29.414 48.016,29.414C51.574,29.414 54.452,32.12 54.452,35.54C54.452,38.961 51.574,41.667 48.016,41.667Z"}
	)
);

const getGIMediaFrame = () => {
	return wp.media.view.MediaFrame.Post.extend( {
		createStates: function createStates() {
			this.states.add( [
				new wp.media.controller.GettyImages({
					id: 'getty-images',
					title: "Getty Images",
					titleMode: 'getty-title-bar',
					content: 'getty-images-browse',
					router: false,
					menu: 'gallery',
					toolbar: 'getty-images-toolbar',
					sidebar: 'getty-image-settings',
					selection: new wp.media.model.Selection(),
				}),
			] );
		},
		
		galleryMenu: function( view ) {
			var lastState = this.lastState(),
				previous = lastState && lastState.id,
				frame = this;
			
			view.set({});
		},
		
	} );
};

class GIMedia extends MediaUpload {
	constructor({value}) {
		super();
		this.openModal = this.openModal.bind(this);
		this.onClose = this.onClose.bind(this);

		const GIMediaFrame = getGIMediaFrame();
		this.frame = new GIMediaFrame({state: 'getty-images'});
		wp.media.frame = this.frame;

		this.frame.on('close', this.onClose );
	}

	componentWillUnmount() {
		this.frame.remove();
	}

	openModal() {
		this.frame.open();
	}

	onClose() {
		const { onSelectImage, onSelectEmbed } = this.props;
		const image = this.frame.state().get('image');
		const attachment = image && image.wpAttachment;
		if (attachment && onSelectImage) {
			return onSelectImage(attachment.attributes);
		}
		
		const embedCode = this.frame.state().get('embedCode');
		if (embedCode) {
			return onSelectEmbed(embedCode);
		}
	}

	render() {
		return this.props.render( { open: this.openModal } );
	}
}

const renderBlock = function({ imgURL, imgID, imgAlt, imgCaption, embedCode }) {
	if (embedCode) {
		return embedCode
	} else if (imgURL) {
		return el('figure', null, [
			el('img', { src: imgURL, alt: imgAlt }),
		]);
	}
}

registerBlockType( 'getty-images/media', {
	title: 'Getty Images',
	icon: gettyLogo,
	category: 'embed',
	keywords: [
		'photos',
		'stock images',
		'editorial images',
	],
	attributes: {
			imgURL: { type: 'string' },
			imgID: { type: 'number' },
			imgAlt: { type: 'string' },
			imgCaption: { type: 'string' },
			embedCode: { type: 'string' },
	},

	edit: function(props) {
		const { setAttributes } = props;
		const onSelectImage = img => {
			setAttributes({
				imgID: img.id,
				imgURL: img.url,
				imgAlt: img.alt || img.caption,
				imgCaption: img.caption
			});
		};
		
		const onSelectEmbed = embedCode => {
			setAttributes({embedCode});
		};

		const block = renderBlock(props.attributes);
		
		if (block) {
			return block;
		} else {
			return el(GIMedia, {
				onSelectImage,
				onSelectEmbed,
				render: ({open}) => el('div', {
					class: 'components-placeholder'
				}, [
					el('div', { 
						class: 'components-placeholder__label' 
					}, [
						gettyLogo,
						"Getty Images"
					]),
					el('Button', { 
						onClick: () => open(),
						class: "components-button editor-media-placeholder__button is-button is-default is-large"
					}, "Select Image")
				])
			});
		}
	},

	save: function(props) {
		return renderBlock(props.attributes);
	}
} );

} // end if (wp.blocks)