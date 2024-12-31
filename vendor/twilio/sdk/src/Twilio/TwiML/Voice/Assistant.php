<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\TwiML\Voice;

use Twilio\TwiML\TwiML;

class Assistant extends TwiML {
    /**
     * Assistant constructor.
     *
     * @param array $attributes Optional attributes
     */
    public function __construct($attributes = []) {
        parent::__construct('Assistant', null, $attributes);
    }

    /**
     * Add Language child.
     *
     * @param array $attributes Optional attributes
     * @return Language Child element.
     */
    public function language($attributes = []): Language {
        return $this->nest(new Language($attributes));
    }

    /**
     * Add Parameter child.
     *
     * @param array $attributes Optional attributes
     * @return Parameter Child element.
     */
    public function parameter($attributes = []): Parameter {
        return $this->nest(new Parameter($attributes));
    }

    /**
     * Add Id attribute.
     *
     * @param string $id The assistant ID of the AI Assistant
     */
    public function setId($id): self {
        return $this->setAttribute('id', $id);
    }

    /**
     * Add Language attribute.
     *
     * @param string $language Language to be used for both text-to-speech and
     *                         transcription
     */
    public function setLanguage($language): self {
        return $this->setAttribute('language', $language);
    }

    /**
     * Add TtsLanguage attribute.
     *
     * @param string $ttsLanguage Language to be used for text-to-speech
     */
    public function setTtsLanguage($ttsLanguage): self {
        return $this->setAttribute('ttsLanguage', $ttsLanguage);
    }

    /**
     * Add TranscriptionLanguage attribute.
     *
     * @param string $transcriptionLanguage Language to be used for transcription
     */
    public function setTranscriptionLanguage($transcriptionLanguage): self {
        return $this->setAttribute('transcriptionLanguage', $transcriptionLanguage);
    }

    /**
     * Add TtsProvider attribute.
     *
     * @param string $ttsProvider Provider to be used for text-to-speech
     */
    public function setTtsProvider($ttsProvider): self {
        return $this->setAttribute('ttsProvider', $ttsProvider);
    }

    /**
     * Add Voice attribute.
     *
     * @param string $voice Voice to be used for text-to-speech
     */
    public function setVoice($voice): self {
        return $this->setAttribute('voice', $voice);
    }

    /**
     * Add TranscriptionProvider attribute.
     *
     * @param string $transcriptionProvider Provider to be used for transcription
     */
    public function setTranscriptionProvider($transcriptionProvider): self {
        return $this->setAttribute('transcriptionProvider', $transcriptionProvider);
    }

    /**
     * Add SpeechModel attribute.
     *
     * @param string $speechModel Speech model to be used for transcription
     */
    public function setSpeechModel($speechModel): self {
        return $this->setAttribute('speechModel', $speechModel);
    }

    /**
     * Add ProfanityFilter attribute.
     *
     * @param bool $profanityFilter Whether profanities should be filtered out of
     *                              the speech transcription
     */
    public function setProfanityFilter($profanityFilter): self {
        return $this->setAttribute('profanityFilter', $profanityFilter);
    }

    /**
     * Add DtmfDetection attribute.
     *
     * @param bool $dtmfDetection Whether DTMF tones should be detected and
     *                            reported in speech transcription
     */
    public function setDtmfDetection($dtmfDetection): self {
        return $this->setAttribute('dtmfDetection', $dtmfDetection);
    }

    /**
     * Add WelcomeGreeting attribute.
     *
     * @param string $welcomeGreeting The sentence to be played automatically when
     *                                the session is connected
     */
    public function setWelcomeGreeting($welcomeGreeting): self {
        return $this->setAttribute('welcomeGreeting', $welcomeGreeting);
    }

    /**
     * Add PartialPrompts attribute.
     *
     * @param bool $partialPrompts Whether partial prompts should be reported to
     *                             WebSocket server before the caller finishes
     *                             speaking
     */
    public function setPartialPrompts($partialPrompts): self {
        return $this->setAttribute('partialPrompts', $partialPrompts);
    }

    /**
     * Add Interruptible attribute.
     *
     * @param bool $interruptible Whether caller's speaking can interrupt the play
     *                            of text-to-speech
     */
    public function setInterruptible($interruptible): self {
        return $this->setAttribute('interruptible', $interruptible);
    }

    /**
     * Add InterruptByDtmf attribute.
     *
     * @param bool $interruptByDtmf Whether DTMF tone can interrupt the play of
     *                              text-to-speech
     */
    public function setInterruptByDtmf($interruptByDtmf): self {
        return $this->setAttribute('interruptByDtmf', $interruptByDtmf);
    }

    /**
     * Add WelcomeGreetingInterruptible attribute.
     *
     * @param bool $welcomeGreetingInterruptible Whether caller's speaking can
     *                                           interrupt the welcome greeting
     */
    public function setWelcomeGreetingInterruptible($welcomeGreetingInterruptible): self {
        return $this->setAttribute('welcomeGreetingInterruptible', $welcomeGreetingInterruptible);
    }

    /**
     * Add Debug attribute.
     *
     * @param bool $debug Whether debugging on the session is enabled
     */
    public function setDebug($debug): self {
        return $this->setAttribute('debug', $debug);
    }
}