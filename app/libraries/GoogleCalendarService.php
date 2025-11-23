<?php
// File: app/libraries/GoogleCalendarService.php

class GoogleCalendarService
{
    private $client;
    private $service;
    
    public function __construct()
    {
        try {
            // Check if Google API client is available
            if (!class_exists('Google_Client')) {
                throw new Exception('Google API Client not found. Install with: composer require google/apiclient');
            }
            
            $this->client = new Google_Client();
            $this->client->setApplicationName('HireTech Job Portal');
            $this->client->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);
            
            // Set the credentials path
            $credentialsPath = __DIR__ . '/../../config/google-calendar-credentials.json';
            if (!file_exists($credentialsPath)) {
                throw new Exception('Google Calendar credentials file not found at: ' . $credentialsPath);
            }
            
            $this->client->setAuthConfig($credentialsPath);
            $this->client->setAccessType('offline');
            
            // For service account authentication
            $this->client->setSubject('hiretech-calendar-service@mapmodal.iam.gserviceaccount.com');
            
            $this->service = new Google_Service_Calendar($this->client);
            
        } catch (Exception $e) {
            error_log("Google Calendar Service Init Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create a calendar event for an interview
     */
    public function createInterviewEvent($interviewData)
    {
        try {
            // Validate required fields
            if (empty($interviewData['start_time'])) {
                throw new Exception('Start time is required');
            }

            // Calculate end time if not provided
            $endTime = $interviewData['end_time'] ?? date('Y-m-d\TH:i:s', strtotime($interviewData['start_time'] . ' +1 hour'));

            // Build event summary and description
            $eventSummary = 'Interview: ' . ($interviewData['job_title'] ?? 'Position') . ' - ' . ($interviewData['applicant_name'] ?? 'Candidate');
            
            // Create event object
            $event = new Google_Service_Calendar_Event([
                'summary' => $eventSummary,
                'location' => $interviewData['location'] ?? 'To be determined',
                'description' => $this->buildInterviewDescription($interviewData),
                'start' => [
                    'dateTime' => $interviewData['start_time'],
                    'timeZone' => 'Asia/Manila',
                ],
                'end' => [
                    'dateTime' => $endTime,
                    'timeZone' => 'Asia/Manila',
                ],
                'reminders' => [
                    'useDefault' => true,
                ],
            ]);

            // Add attendees if emails are provided
            $attendees = [];
            if (!empty($interviewData['applicant_email'])) {
                $attendees[] = ['email' => $interviewData['applicant_email']];
            }
            if (!empty($interviewData['interviewer_email'])) {
                $attendees[] = ['email' => $interviewData['interviewer_email']];
            }
            
            if (!empty($attendees)) {
                $event->setAttendees($attendees);
            }

            // Insert event
            $calendarId = 'primary';
            $event = $this->service->events->insert($calendarId, $event, [
                'sendUpdates' => 'all' // Send notifications to attendees
            ]);

            return [
                'success' => true,
                'event_id' => $event->getId(),
                'html_link' => $event->getHtmlLink(),
                'hangout_link' => $event->getHangoutLink() ?? null
            ];
            
        } catch (Google_Service_Exception $e) {
            $errorMessage = "Google API Error: " . $e->getMessage();
            error_log($errorMessage);
            return [
                'success' => false,
                'error' => $errorMessage,
                'code' => $e->getCode()
            ];
        } catch (Exception $e) {
            $errorMessage = "Calendar Event Creation Error: " . $e->getMessage();
            error_log($errorMessage);
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }
    }
    
    /**
     * Build interview description
     */
    private function buildInterviewDescription($interviewData)
    {
        $description = "Job Interview Details:\n\n";
        $description .= "Position: " . ($interviewData['job_title'] ?? 'N/A') . "\n";
        $description .= "Company: " . ($interviewData['company'] ?? 'N/A') . "\n";
        $description .= "Applicant: " . ($interviewData['applicant_name'] ?? 'N/A') . "\n";
        $description .= "Interview Type: " . ($interviewData['interview_type'] ?? 'N/A') . "\n";
        
        if (!empty($interviewData['location'])) {
            $description .= "Location: " . $interviewData['location'] . "\n";
        }
        
        if (!empty($interviewData['interview_notes'])) {
            $description .= "\nInterview Notes:\n" . $interviewData['interview_notes'] . "\n";
        }
        
        $description .= "\nThis event was created automatically by HireTech Job Portal.";
        
        return $description;
    }
    
    /**
     * Delete a calendar event
     */
    public function deleteEvent($eventId)
    {
        try {
            $this->service->events->delete('primary', $eventId);
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Google Calendar Delete Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if service is properly configured
     */
    public function isConfigured()
    {
        try {
            $this->service->events->listEvents('primary', ['maxResults' => 1]);
            return true;
        } catch (Exception $e) {
            error_log("Google Calendar Configuration Check Failed: " . $e->getMessage());
            return false;
        }
    }
}
?>