<?php

namespace hkyss\Extras\Enums;

enum CommandOptions: string
{

    case VERSION = 'version';
    case FORCE = 'force';
    case DRY_RUN = 'dry-run';
    case VERBOSE = 'verbose';
    case QUIET = 'quiet';
    

    case FILE = 'file';
    case CONTINUE_ON_ERROR = 'continue-on-error';
    case PARALLEL = 'parallel';
    

    case SEARCH = 'search';
    case FORMAT = 'format';
    case INTERACTIVE = 'interactive';
    case INSTALLED = 'installed';
    case DEPENDENCIES = 'dependencies';
    case RELEASES = 'releases';
    case CHECK_ONLY = 'check-only';
    case KEEP_DEPS = 'keep-deps';
    case NO_DEPS = 'no-deps';
    

    case CLEAR = 'clear';
    case STATUS = 'status';
    case REFRESH = 'refresh';
    case STATS = 'stats';
    case ALL = 'all';
    




}
