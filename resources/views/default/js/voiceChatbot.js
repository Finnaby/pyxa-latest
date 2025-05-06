import { Alpine } from '~vendor/livewire/livewire/dist/livewire.esm';
import { Conversation } from '@11labs/client';
import { Recorder } from './components/realtime-frontend/recorder.js';

const elevenLabsConversationalAI = ( agentId, botUuid ) => ( {
	/**@type {String} */
	agentId: agentId,
	/**@type {String} */
	uuId: botUuid,
	/**@type {String} */
	bubbleMessage: 'Need help?',
	/**@type {Conversation} */
	coversation: null,
	/**@type {Recorder} */
	audioRecorder: null,
	/**@type {HTMLElement} */
	chatbotStatus: null,
	/**@type {HTMLElement} */
	startConversationBtn: null,
	/**@type {HTMLElement} */
	stopConversationBtn: null,
	/**@type {HTMLElement} */
	audioVisEl: null,

	init() {
		this.chatbotStatus = document.getElementById( 'lqd-ext-chatbot-voice-bot-status' );
		this.bubbleMessage = this.chatbotStatus.textContent;
		this.startConversationBtn = document.getElementById( 'lqd-ext-chatbot-voice-start-btn' );
		this.stopConversationBtn = document.getElementById( 'lqd-ext-chatbot-voice-end-btn' );
		this.audioVisEl = document.getElementById( 'lqd-ext-chatbot-voice-vis-img' );

		this.initRecorder();
		this.addEventListeners();
	},
	// add event listeners
	addEventListeners() {
		this.startConversationBtn.addEventListener( 'click', () => this.startConversation() );
		this.stopConversationBtn.addEventListener( 'click', () => this.stopConversation() );
	},
	// start conversation
	async startConversation() {
		try {
			// disable the btn to prevent double click
			this.startConversationBtn.setAttribute( 'disabled', true );
			this.startConversationBtn.querySelector( 'span' ).textContent = 'starting...';

			// request microphone permission
			const stream = await navigator.mediaDevices.getUserMedia( { audio: true, video: false } );

			this.conversation = await Conversation.startSession( {
				agentId: this.agentId,
				onConnect: async () => {
					this.startConversationBtn.style.display = 'none';
					this.stopConversationBtn.style.display = 'flex';

					await this.audioRecorder?.start( stream );
					this.startDotVisualizer();
				},
				onDisconnect: () => {
					this.startConversationBtn.style.display = 'flex';
					this.stopConversationBtn.style.display = 'none';
					this.chatbotStatus.textContent = this.bubbleMessage;

					// reset to origin
					if ( this.audioVisEl ) {
						this.audioVisEl.style.transform = 'scale(1)';
						this.audioVisEl.style.opacity = 1;
					}

					this.storeConversation( this.conversation.getId() );

					this.audioRecorder?.stop();
				},
				onModeChange: mode => {
					this.chatbotStatus.textContent = mode.mode === 'speaking' ? 'speaking' : 'listening';
				},
				onError: error => {
					console.error( 'Error:', error );
				}
			} )

			// Enable button
			this.startConversationBtn.setAttribute( 'disabled', false );
			this.startConversationBtn.querySelector( 'span' ).textContent = 'Voice Chat';
		} catch ( error ) {
			console.error( 'Failed to start conversation:', error );
		}
	},
	// stop conversation
	async stopConversation() {
		if ( this.conversation ) {
			await this.conversation.endSession();
			this.conversation = null;
		}
	},
	// start recorder (this is needed for conversation status visualization)
	async initRecorder() {
		try {
			this.audioRecorder = new Recorder( this.handleAudioRecordingBuffer );
		} catch ( error ) {
			console.error( 'Error starting audio recorder:', error );
		}
	},
	// create conversation
	async storeConversation( conversationId ) {
		const res = await fetch( `/api/v2/chatbot-voice/${ this.uuId }/store-conversation`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json'
			},
			body: JSON.stringify( {
				'conversation_id': conversationId
			} )
		} );

		try {
			const resData = await res.json();

			if ( !res.ok ) {
				console.error( 'Failed create conversation:', resData.message );
			}
		} catch ( error ) {
			console.error( 'Failed parse JSON:', error );
		}
	},
	handleAudioRecordingBuffer( data ) { },
	startDotVisualizer() {
		if ( !this.audioRecorder || !this.audioVisEl ) return;

		const analyser = this.audioRecorder.audioContext.createAnalyser();
		analyser.fftSize = 256;
		const bufferLength = analyser.frequencyBinCount;
		const dataArray = new Uint8Array( bufferLength );

		this.audioRecorder.getMediaStreamSource().connect( analyser );

		if ( !this.audioVisEl ) return;

		const animate = () => {
			analyser.getByteFrequencyData( dataArray );

			let sum = 0;
			for ( let i = 0; i < bufferLength; i++ ) {
				sum += dataArray[ i ];
			}
			const average = sum / bufferLength;

			const scale = 1 + ( average / 256 ) * 1.2;
			const opacity = Math.max( 0.2, 1 - ( scale - 1 ) / 1.5 ); // Minimum opacity of 0.2

			this.audioVisEl.style.transform = `scale(${ scale })`;
			this.audioVisEl.style.opacity = opacity.toFixed( 2 ); // Limit to two decimal places

			requestAnimationFrame( animate );
		}

		animate();
	},
} );

window.Alpine = Alpine;
document.addEventListener( 'alpine:init', () => {
	Alpine.data( 'elevenLabsConversationalAI', elevenLabsConversationalAI );
} );
