<?php
/**
 * AI Client - OpenAI API Wrapper
 * 
 * Handles communication with OpenAI API for design generation,
 * including text prompts, image references (Vision API), and iterative refinement.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STB_AI_Client {

	private $api_key;
	private $model;
	private $api_url = 'https://api.openai.com/v1/chat/completions';
	private $timeout = 120;
	private $cheatsheet_content = '';
	private $design_md_content = '';

	public function __construct() {
		$this->api_key = get_option( 'stb_openai_api_key', '' );
		$this->model = get_option( 'stb_openai_model', 'gpt-4o-mini' );
		$this->load_cheatsheet();
		$this->load_design_md();
	}

	private function load_cheatsheet() {
		$cheatsheet = get_option( 'stb_cheatsheet', '' );
		if ( ! empty( $cheatsheet ) ) {
			$this->cheatsheet_content = $cheatsheet;
			return;
		}

		$cheatsheet_path = STB_PLUGIN_DIR . 'core-framework-cheatsheet.md';
		if ( file_exists( $cheatsheet_path ) ) {
			$this->cheatsheet_content = file_get_contents( $cheatsheet_path );
		}
	}

	public function get_api_key() {
		return $this->api_key;
	}

	public function get_model() {
		return $this->model;
	}

	public function test_connection( $test_api_key = null ) {
		$key_to_test = $test_api_key ?? $this->api_key;
		
		if ( empty( $key_to_test ) ) {
			return new WP_Error( 'missing_api_key', 'OpenAI API key is not configured.' );
		}

		$response = wp_remote_get( 'https://api.openai.com/v1/models', [
			'headers' => [
				'Authorization' => 'Bearer ' . $key_to_test,
			],
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code === 200 ) {
			return true;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$message = isset( $body['error']['message'] ) ? $body['error']['message'] : 'Unknown error';
		return new WP_Error( 'api_error', $message, [ 'status' => $status_code ] );
	}

	public function generate_design( $prompt, $images = [], $conversation_history = [] ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'missing_api_key', 'OpenAI API key is not configured.' );
		}

		$system_prompt = $this->build_system_prompt();
		$messages = $this->build_messages( $system_prompt, $prompt, $images, $conversation_history );

		$body = [
			'model' => $this->model,
			'messages' => $messages,
			'temperature' => 0.7,
			'max_tokens' => 4000,
			'response_format' => [ 'type' => 'json_object' ],
		];

		$response = wp_remote_post( $this->api_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => $this->timeout,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$message = isset( $body['error']['message'] ) ? $body['error']['message'] : 'API request failed';
			return new WP_Error( 'api_error', $message, [ 'status' => $status_code ] );
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $this->parse_response( $response_body );
	}

	public function refine_design( $conversation_history, $feedback, $new_images = [] ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'missing_api_key', 'OpenAI API key is not configured.' );
		}

		$system_prompt = $this->build_system_prompt();
		$messages = array_merge( [ $system_prompt ], $conversation_history );

		if ( ! empty( $new_images ) ) {
			$content = [ [ 'type' => 'text', 'text' => $feedback ] ];
			foreach ( $new_images as $image ) {
				$content[] = $this->format_image_for_api( $image );
			}
			$messages[] = [ 'role' => 'user', 'content' => $content ];
		} else {
			$messages[] = [ 'role' => 'user', 'content' => $feedback ];
		}

		$body = [
			'model' => $this->model,
			'messages' => $messages,
			'temperature' => 0.7,
			'max_tokens' => 4000,
			'response_format' => [ 'type' => 'json_object' ],
		];

		$response = wp_remote_post( $this->api_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => $this->timeout,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$message = isset( $body['error']['message'] ) ? $body['error']['message'] : 'API request failed';
			return new WP_Error( 'api_error', $message, [ 'status' => $status_code ] );
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $this->parse_response( $response_body );
	}

	public function estimate_tokens( $text ) {
		$chars = strlen( $text );
		return ceil( $chars / 4 );
	}

	public function get_usage_estimate( $prompt_tokens, $completion_tokens ) {
		$pricing = [
			'gpt-4o' => [ 'input' => 2.50, 'output' => 10.00 ],
			'gpt-4o-mini' => [ 'input' => 0.150, 'output' => 0.600 ],
		];

		$model = $this->model;
		if ( ! isset( $pricing[ $model ] ) ) {
			$model = 'gpt-4o-mini';
		}

		$input_cost = ( $prompt_tokens / 1000000 ) * $pricing[ $model ]['input'];
		$output_cost = ( $completion_tokens / 1000000 ) * $pricing[ $model ]['output'];

		return [
			'prompt_tokens' => $prompt_tokens,
			'completion_tokens' => $completion_tokens,
			'total_tokens' => $prompt_tokens + $completion_tokens,
			'estimated_cost_usd' => round( $input_cost + $output_cost, 6 ),
			'model' => $model,
		];
	}

	private function load_design_md() {
		$design_md = get_option( 'stb_design_md', '' );
		if ( ! empty( $design_md ) ) {
			$this->design_md_content = $design_md;
			return;
		}

		$design_md_path = STB_PLUGIN_DIR . 'DESIGN.md';
		if ( file_exists( $design_md_path ) ) {
			$this->design_md_content = file_get_contents( $design_md_path );
		}
	}

	private function build_system_prompt() {
		$cheatsheet = $this->cheatsheet_content;
		$design_md = $this->design_md_content;

		$system_prompt = "You are an expert frontend developer specializing in creating clean, semantic HTML/CSS using the Core Framework design system. Your output will be imported into Bricks Builder via native HTML & CSS import.

### DESIGN CONTRACT (DESIGN.md)
{$design_md}

### TECHNICAL REFERENCE (CHEATSHEET)
{$cheatsheet}

CRITICAL OUTPUT FORMAT:
You must respond with a valid JSON object containing:
{
  \"html\": \"<section>...</section>\",
  \"css\": \".custom-class { ... }\",
  \"description\": \"Brief description of the design\",
  \"tokens_used\": 1234
}

RULES:
- HTML must be fragment-ready (no <html>, <head>, or <body> wrappers)
- Use Core Framework classes exclusively (.btn, .card, .badge, .padding-*, .bg-*, etc.)
- Use CSS variables for all colors, spacing, typography, and shadows
- No Tailwind classes, CDN scripts, or configuration
- No external stylesheets, scripts, or @import rules
- Use inline SVG for icons, never remote icon fonts
- If custom CSS is absolutely needed, keep it minimal and scoped to component root
- Keep HTML semantic and Bricks-friendly
- Escape all quotes in JSON strings properly

Return ONLY the JSON object, no markdown formatting or additional text.";

		return [
			'role'    => 'system',
			'content' => $system_prompt,
		];
	}

	private function build_messages( $system_prompt, $prompt, $images, $conversation_history ) {
		$messages = [ $system_prompt ];

		if ( ! empty( $conversation_history ) ) {
			$messages = array_merge( $messages, $conversation_history );
		}

		if ( ! empty( $images ) ) {
			$content = [ [ 'type' => 'text', 'text' => $prompt ] ];
			foreach ( $images as $image ) {
				$content[] = $this->format_image_for_api( $image );
			}
			$messages[] = [ 'role' => 'user', 'content' => $content ];
		} else {
			$messages[] = [ 'role' => 'user', 'content' => $prompt ];
		}

		return $messages;
	}

	private function format_image_for_api( $image ) {
		if ( isset( $image['url'] ) ) {
			return [
				'type' => 'image_url',
				'image_url' => [
					'url' => $image['url'],
					'detail' => isset( $image['detail'] ) ? $image['detail'] : 'high',
				],
			];
		}

		if ( isset( $image['base64'] ) ) {
			return [
				'type' => 'image_url',
				'image_url' => [
					'url' => 'data:' . ( $image['mime_type'] ?? 'image/png' ) . ';base64,' . $image['base64'],
					'detail' => isset( $image['detail'] ) ? $image['detail'] : 'high',
				],
			];
		}

		return null;
	}

	private function parse_response( $response_body ) {
		if ( ! isset( $response_body['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'invalid_response', 'Unexpected API response structure.' );
		}

		$content = $response_body['choices'][0]['message']['content'];
		$parsed = json_decode( $content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'json_parse_error', 'Failed to parse AI response as JSON: ' . json_last_error_msg(), [ 'raw_content' => $content ] );
		}

		$usage = $response_body['usage'] ?? [];
		$prompt_tokens = $usage['prompt_tokens'] ?? 0;
		$completion_tokens = $usage['completion_tokens'] ?? 0;

		$usage_stats = $this->get_usage_estimate( $prompt_tokens, $completion_tokens );
		$parsed['usage'] = $usage_stats;
		$parsed['model'] = $this->model;

		$this->log_usage( $usage_stats );

		return $parsed;
	}

	public function generate_cheatsheet( $css_content ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'missing_api_key', 'OpenAI API key is not configured.' );
		}

		$system_prompt = "You are an expert CSS parser and design system architect. Your task is to analyze a provided CSS file and generate a concise, structured reference guide (cheatsheet) for an AI code generator.

The cheatsheet MUST follow this exact structure:
1. CSS Variables (Colors, Spacing, Typography, Radius, Shadows, Layout)
2. Component Classes (Buttons, Badges, Cards, Links, Inputs, Icons, Dividers)
3. Utility Families (Backgrounds, Text Colors, Borders, Spacing, Layout)
4. Theme Wrappers
5. Generation Rules (10 strict rules for AI output)

RULES FOR OUTPUT:
- Do NOT include the full CSS. Only extract the most important classes and variables.
- Use markdown formatting.
- Keep it under 500 lines.
- Focus on patterns (e.g., `.bg-primary`, `.bg-primary-10`, etc.) rather than listing every single variant if they follow a pattern.
- The goal is to give an AI enough context to use the framework correctly without wasting tokens.

Return ONLY the markdown cheatsheet content. No extra text.";

		$messages = [
			[ 'role' => 'system', 'content' => $system_prompt ],
			[ 'role' => 'user', 'content' => "Here is the Core Framework CSS. Generate the cheatsheet:\n\n```css\n{$css_content}\n```" ]
		];

		$body = [
			'model' => $this->model,
			'messages' => $messages,
			'temperature' => 0.3,
			'max_tokens' => 4000,
		];

		$response = wp_remote_post( $this->api_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 120,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$message = isset( $body['error']['message'] ) ? $body['error']['message'] : 'API request failed';
			return new WP_Error( 'api_error', $message, [ 'status' => $status_code ] );
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $response_body['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'invalid_response', 'Unexpected API response structure.' );
		}

		return trim( $response_body['choices'][0]['message']['content'] );
	}

	public function log_usage( $usage ) {
		$current = get_option( 'stb_ai_usage', [
			'total_tokens' => 0,
			'total_cost'   => 0.0,
			'requests'     => 0,
			'last_reset'   => gmdate( 'Y-m-d' ),
		] );

		$today = gmdate( 'Y-m-d' );
		if ( $current['last_reset'] !== $today ) {
			$current = [
				'total_tokens' => 0,
				'total_cost'   => 0.0,
				'requests'     => 0,
				'last_reset'   => $today,
			];
		}

		$current['total_tokens'] += $usage['total_tokens'] ?? 0;
		$current['total_cost']   += $usage['estimated_cost_usd'] ?? 0.0;
		$current['requests']     += 1;

		update_option( 'stb_ai_usage', $current );
	}

	public function get_usage_stats() {
		return get_option( 'stb_ai_usage', [
			'total_tokens' => 0,
			'total_cost'   => 0.0,
			'requests'     => 0,
			'last_reset'   => gmdate( 'Y-m-d' ),
		] );
	}
}
