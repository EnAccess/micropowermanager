<?php

namespace Tests\Unit;

use App\Helpers\MailHelperStdout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailHelperStdoutTest extends TestCase {
    use RefreshDatabase;

    private MailHelperStdout $mailHelper;

    protected function setUp(): void {
        parent::setUp();
        $this->mailHelper = new MailHelperStdout();
    }

    public function testSendPlainEmailOutputsToStdout() {
        // Capture output
        ob_start();

        $this->mailHelper->sendPlain(
            'test@example.com',
            'Test Subject',
            'Test email body content'
        );

        $output = ob_get_clean();

        // Assert that the output contains expected content
        $this->assertStringContainsString('EMAIL SENT TO STDOUT', $output);
        $this->assertStringContainsString('test@example.com', $output);
        $this->assertStringContainsString('Test Subject', $output);
        $this->assertStringContainsString('Test email body content', $output);
        $this->assertStringContainsString('Attachment: None', $output);
    }

    public function testSendPlainEmailWithAttachmentOutputsToStdout() {
        // Capture output
        ob_start();

        $this->mailHelper->sendPlain(
            'test@example.com',
            'Test Subject',
            'Test email body content',
            '/path/to/attachment.pdf'
        );

        $output = ob_get_clean();

        // Assert that the output contains expected content
        $this->assertStringContainsString('EMAIL SENT TO STDOUT', $output);
        $this->assertStringContainsString('test@example.com', $output);
        $this->assertStringContainsString('Test Subject', $output);
        $this->assertStringContainsString('Test email body content', $output);
        $this->assertStringContainsString('/path/to/attachment.pdf', $output);
    }

    public function testSendViaTemplateOutputsToStdout() {
        // Create a simple test view
        $viewPath = resource_path('views/emails/test-template.blade.php');
        $viewDir = dirname($viewPath);

        if (!is_dir($viewDir)) {
            mkdir($viewDir, 0755, true);
        }

        file_put_contents($viewPath, '<h1>Hello {{ $name }}</h1><p>{{ $message }}</p>');

        // Capture output
        ob_start();

        $this->mailHelper->sendViaTemplate(
            'test@example.com',
            'Test Template Subject',
            'emails.test-template',
            ['name' => 'John Doe', 'message' => 'Hello World']
        );

        $output = ob_get_clean();

        // Clean up
        unlink($viewPath);
        if (is_dir($viewDir) && count(scandir($viewDir)) <= 2) {
            rmdir($viewDir);
        }

        // Assert that the output contains expected content
        $this->assertStringContainsString('EMAIL SENT TO STDOUT', $output);
        $this->assertStringContainsString('test@example.com', $output);
        $this->assertStringContainsString('Test Template Subject', $output);
        $this->assertStringContainsString('Hello John Doe', $output);
        $this->assertStringContainsString('Hello World', $output);
    }
}
