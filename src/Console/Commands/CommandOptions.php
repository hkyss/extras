<?php

namespace hkyss\Extras\Console\Commands;

enum CommandOptions: string
{
    // Common options
    case VERSION = 'version';
    case FORCE = 'force';
    case DRY_RUN = 'dry-run';
    case VERBOSE = 'verbose';
    case QUIET = 'quiet';
    
    // Batch options
    case FILE = 'file';
    case CONTINUE_ON_ERROR = 'continue-on-error';
    case PARALLEL = 'parallel';
    
    // Specific options
    case SEARCH = 'search';
    case FORMAT = 'format';
    case INTERACTIVE = 'interactive';
    case INSTALLED = 'installed';
    case DEPENDENCIES = 'dependencies';
    case RELEASES = 'releases';
    case CHECK_ONLY = 'check-only';
    case KEEP_DEPS = 'keep-deps';
    case NO_DEPS = 'no-deps';
    
    // Cache options
    case CLEAR = 'clear';
    case STATUS = 'status';
    case REFRESH = 'refresh';
    case STATS = 'stats';
    
    // Legacy options (for backward compatibility)
    case INSTALL_VERSION = 'install-version';
    case UPDATE_VERSION = 'update-version';
    case INSTALL_FORCE = 'install-force';
    case UPDATE_FORCE = 'update-force';
    case REMOVE_FORCE = 'remove-force';
    case BATCH_INSTALL_FORCE = 'batch-install-force';
    case BATCH_UPDATE_FORCE = 'batch-update-force';
    case BATCH_REMOVE_FORCE = 'batch-remove-force';
    case BATCH_INSTALL_CONTINUE_ON_ERROR = 'batch-install-continue-on-error';
    case BATCH_UPDATE_CONTINUE_ON_ERROR = 'batch-update-continue-on-error';
    case BATCH_REMOVE_CONTINUE_ON_ERROR = 'batch-remove-continue-on-error';
    case BATCH_INSTALL_DRY_RUN = 'batch-install-dry-run';
    case BATCH_UPDATE_DRY_RUN = 'batch-update-dry-run';
    case BATCH_REMOVE_DRY_RUN = 'batch-remove-dry-run';
    case BATCH_INSTALL_PARALLEL = 'batch-install-parallel';
    case BATCH_UPDATE_PARALLEL = 'batch-update-parallel';
    case BATCH_REMOVE_PARALLEL = 'batch-remove-parallel';
    case BATCH_KEEP_DEPS = 'batch-keep-deps';
    case REMOVE_KEEP_DEPS = 'remove-keep-deps';
    case INSTALL_FILE = 'install-file';
    case UPDATE_FILE = 'update-file';
    case REMOVE_FILE = 'remove-file';
    case LIST_FORMAT = 'list-format';
    case INFO_FORMAT = 'info-format';
    case BATCH_CHECK_ONLY = 'batch-check-only';
    case ALL = 'all';

    /**
     * Get option name with prefix
     */
    public function withPrefix(string $prefix): string
    {
        return $prefix . '-' . $this->value;
    }

    /**
     * Get legacy option name
     */
    public function getLegacyName(): string
    {
        return match($this) {
            self::VERSION => self::INSTALL_VERSION->value,
            self::FORCE => self::INSTALL_FORCE->value,
            self::DRY_RUN => self::BATCH_INSTALL_DRY_RUN->value,
            self::CONTINUE_ON_ERROR => self::BATCH_INSTALL_CONTINUE_ON_ERROR->value,
            self::PARALLEL => self::BATCH_INSTALL_PARALLEL->value,
            self::FILE => self::INSTALL_FILE->value,
            self::KEEP_DEPS => self::REMOVE_KEEP_DEPS->value,
            self::FORMAT => self::LIST_FORMAT->value,
            default => $this->value
        };
    }

    /**
     * Get all legacy options
     */
    public static function getLegacyOptions(): array
    {
        return [
            self::INSTALL_VERSION,
            self::UPDATE_VERSION,
            self::INSTALL_FORCE,
            self::UPDATE_FORCE,
            self::REMOVE_FORCE,
            self::BATCH_INSTALL_FORCE,
            self::BATCH_UPDATE_FORCE,
            self::BATCH_REMOVE_FORCE,
            self::BATCH_INSTALL_CONTINUE_ON_ERROR,
            self::BATCH_UPDATE_CONTINUE_ON_ERROR,
            self::BATCH_REMOVE_CONTINUE_ON_ERROR,
            self::BATCH_INSTALL_DRY_RUN,
            self::BATCH_UPDATE_DRY_RUN,
            self::BATCH_REMOVE_DRY_RUN,
            self::BATCH_INSTALL_PARALLEL,
            self::BATCH_UPDATE_PARALLEL,
            self::BATCH_REMOVE_PARALLEL,
            self::BATCH_KEEP_DEPS,
            self::REMOVE_KEEP_DEPS,
            self::INSTALL_FILE,
            self::UPDATE_FILE,
            self::REMOVE_FILE,
            self::LIST_FORMAT,
            self::INFO_FORMAT,
            self::BATCH_CHECK_ONLY,
            self::ALL
        ];
    }

    /**
     * Check if option is legacy
     */
    public function isLegacy(): bool
    {
        return in_array($this, self::getLegacyOptions());
    }

    /**
     * Get modern equivalent for legacy option
     */
    public function getModernEquivalent(): ?self
    {
        return match($this) {
            self::INSTALL_VERSION, self::UPDATE_VERSION => self::VERSION,
            self::INSTALL_FORCE, self::UPDATE_FORCE, self::REMOVE_FORCE,
            self::BATCH_INSTALL_FORCE, self::BATCH_UPDATE_FORCE, self::BATCH_REMOVE_FORCE => self::FORCE,
            self::BATCH_INSTALL_DRY_RUN, self::BATCH_UPDATE_DRY_RUN, self::BATCH_REMOVE_DRY_RUN => self::DRY_RUN,
            self::BATCH_INSTALL_CONTINUE_ON_ERROR, self::BATCH_UPDATE_CONTINUE_ON_ERROR, self::BATCH_REMOVE_CONTINUE_ON_ERROR => self::CONTINUE_ON_ERROR,
            self::BATCH_INSTALL_PARALLEL, self::BATCH_UPDATE_PARALLEL, self::BATCH_REMOVE_PARALLEL => self::PARALLEL,
            self::INSTALL_FILE, self::UPDATE_FILE, self::REMOVE_FILE => self::FILE,
            self::REMOVE_KEEP_DEPS, self::BATCH_KEEP_DEPS => self::KEEP_DEPS,
            self::LIST_FORMAT, self::INFO_FORMAT => self::FORMAT,
            default => null
        };
    }
}
