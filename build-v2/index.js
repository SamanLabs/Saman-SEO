/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src-v2/App.js"
/*!***********************!*\
  !*** ./src-v2/App.js ***!
  \***********************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_Header__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/Header */ "./src-v2/components/Header.js");
/* harmony import */ var _pages_Dashboard__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./pages/Dashboard */ "./src-v2/pages/Dashboard.js");
/* harmony import */ var _pages_SearchAppearance__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./pages/SearchAppearance */ "./src-v2/pages/SearchAppearance.js");
/* harmony import */ var _pages_Sitemap__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./pages/Sitemap */ "./src-v2/pages/Sitemap.js");
/* harmony import */ var _pages_Tools__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./pages/Tools */ "./src-v2/pages/Tools.js");
/* harmony import */ var _pages_Redirects__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./pages/Redirects */ "./src-v2/pages/Redirects.js");
/* harmony import */ var _pages_Log404__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./pages/Log404 */ "./src-v2/pages/Log404.js");
/* harmony import */ var _pages_InternalLinking__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./pages/InternalLinking */ "./src-v2/pages/InternalLinking.js");
/* harmony import */ var _pages_Audit__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./pages/Audit */ "./src-v2/pages/Audit.js");
/* harmony import */ var _pages_AiAssistant__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./pages/AiAssistant */ "./src-v2/pages/AiAssistant.js");
/* harmony import */ var _pages_Assistants__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./pages/Assistants */ "./src-v2/pages/Assistants.js");
/* harmony import */ var _pages_Settings__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./pages/Settings */ "./src-v2/pages/Settings.js");
/* harmony import */ var _pages_More__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./pages/More */ "./src-v2/pages/More.js");
/* harmony import */ var _pages_Setup__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./pages/Setup */ "./src-v2/pages/Setup.js");
/* harmony import */ var _pages_BulkEditor__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./pages/BulkEditor */ "./src-v2/pages/BulkEditor.js");
/* harmony import */ var _pages_ContentGaps__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./pages/ContentGaps */ "./src-v2/pages/ContentGaps.js");
/* harmony import */ var _pages_SchemaBuilder__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./pages/SchemaBuilder */ "./src-v2/pages/SchemaBuilder.js");
/* harmony import */ var _index_css__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./index.css */ "./src-v2/index.css");





















const viewToPage = {
  dashboard: 'wpseopilot-dashboard',
  'search-appearance': 'wpseopilot-search-appearance',
  sitemap: 'wpseopilot-sitemap',
  tools: 'wpseopilot-tools',
  redirects: 'wpseopilot-redirects',
  '404-log': 'wpseopilot-404-log',
  'internal-linking': 'wpseopilot-internal-linking',
  audit: 'wpseopilot-audit',
  'ai-assistant': 'wpseopilot-ai-assistant',
  assistants: 'wpseopilot-assistants',
  settings: 'wpseopilot-settings',
  more: 'wpseopilot-more',
  'bulk-editor': 'wpseopilot-bulk-editor',
  'content-gaps': 'wpseopilot-content-gaps',
  'schema-builder': 'wpseopilot-schema-builder'
};
const pageToView = Object.entries(viewToPage).reduce((acc, [view, page]) => {
  acc[page] = view;
  return acc;
}, {});
const App = ({
  initialView = 'dashboard'
}) => {
  const [currentView, setCurrentView] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(initialView);
  const [showSetup, setShowSetup] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [setupChecked, setSetupChecked] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);

  // Check setup status on mount
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const checkSetupStatus = async () => {
      try {
        const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
          path: '/wpseopilot/v2/setup/status'
        });
        if (response.success && response.data.show_wizard) {
          setShowSetup(true);
        }
      } catch (err) {
        // Ignore errors, just show the app
      }
      setSetupChecked(true);
    };
    checkSetupStatus();
  }, []);
  const handleSetupComplete = () => {
    setShowSetup(false);
    setCurrentView('dashboard');
  };
  const handleSetupSkip = () => {
    setShowSetup(false);
  };
  const updateAdminMenuHighlight = (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)(view => {
    if (typeof document === 'undefined') {
      return;
    }
    const menu = document.getElementById('toplevel_page_wpseopilot');
    if (!menu) {
      return;
    }
    const submenuLinks = menu.querySelectorAll('.wp-submenu a[href*="page=wpseopilot"]');
    submenuLinks.forEach(link => {
      link.removeAttribute('aria-current');
      const listItem = link.closest('li');
      if (listItem) {
        listItem.classList.remove('current');
      }
    });
    const page = viewToPage[view] || viewToPage.dashboard;
    const activeLink = menu.querySelector(`.wp-submenu a[href*="page=${page}"]`);
    if (activeLink) {
      activeLink.setAttribute('aria-current', 'page');
      const listItem = activeLink.closest('li');
      if (listItem) {
        listItem.classList.add('current');
      }
    }
    menu.classList.add('current', 'wp-has-current-submenu');
  }, []);
  const handleNavigate = (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)(view => {
    if (view === currentView) {
      return;
    }
    setCurrentView(view);
    if (typeof window === 'undefined') {
      return;
    }
    const page = viewToPage[view] || viewToPage.dashboard;
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    url.searchParams.delete('tab');
    window.history.pushState({}, '', url.toString());
    updateAdminMenuHighlight(view);
  }, [currentView, updateAdminMenuHighlight]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const handlePopState = () => {
      const url = new URL(window.location.href);
      const page = url.searchParams.get('page');
      if (page && pageToView[page]) {
        setCurrentView(pageToView[page]);
      }
    };
    window.addEventListener('popstate', handlePopState);
    return () => window.removeEventListener('popstate', handlePopState);
  }, []);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    updateAdminMenuHighlight(currentView);
  }, [currentView, updateAdminMenuHighlight]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const handleMenuClick = event => {
      const link = event.target.closest('a');
      if (!link || typeof window === 'undefined') {
        return;
      }
      const menu = document.getElementById('toplevel_page_wpseopilot');
      if (!menu || !menu.contains(link)) {
        return;
      }
      const href = link.getAttribute('href');
      if (!href || !href.includes('page=wpseopilot')) {
        return;
      }
      const url = new URL(href, window.location.origin);
      const page = url.searchParams.get('page');
      if (!page || !pageToView[page]) {
        return;
      }
      event.preventDefault();
      handleNavigate(pageToView[page]);
    };
    document.addEventListener('click', handleMenuClick);
    return () => document.removeEventListener('click', handleMenuClick);
  }, [handleNavigate]);
  const renderView = () => {
    switch (currentView) {
      case 'search-appearance':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_SearchAppearance__WEBPACK_IMPORTED_MODULE_4__["default"], null);
      case 'sitemap':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_Sitemap__WEBPACK_IMPORTED_MODULE_5__["default"], null);
      case 'tools':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_Tools__WEBPACK_IMPORTED_MODULE_6__["default"], {
          onNavigate: handleNavigate
        });
      case 'redirects':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_Redirects__WEBPACK_IMPORTED_MODULE_7__["default"], null);
      case '404-log':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_Log404__WEBPACK_IMPORTED_MODULE_8__["default"], {
          onNavigate: handleNavigate
        });
      case 'internal-linking':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_InternalLinking__WEBPACK_IMPORTED_MODULE_9__["default"], null);
      case 'audit':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_Audit__WEBPACK_IMPORTED_MODULE_10__["default"], null);
      case 'ai-assistant':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_AiAssistant__WEBPACK_IMPORTED_MODULE_11__["default"], null);
      case 'assistants':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_Assistants__WEBPACK_IMPORTED_MODULE_12__["default"], null);
      case 'settings':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_Settings__WEBPACK_IMPORTED_MODULE_13__["default"], null);
      case 'more':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_More__WEBPACK_IMPORTED_MODULE_14__["default"], null);
      case 'bulk-editor':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_BulkEditor__WEBPACK_IMPORTED_MODULE_16__["default"], {
          onNavigate: handleNavigate
        });
      case 'content-gaps':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_ContentGaps__WEBPACK_IMPORTED_MODULE_17__["default"], {
          onNavigate: handleNavigate
        });
      case 'schema-builder':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_SchemaBuilder__WEBPACK_IMPORTED_MODULE_18__["default"], {
          onNavigate: handleNavigate
        });
      default:
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_Dashboard__WEBPACK_IMPORTED_MODULE_3__["default"], {
          onNavigate: handleNavigate
        });
    }
  };

  // Show loading while checking setup status
  if (!setupChecked) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "wp-seo-pilot-admin"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "wp-seo-pilot-shell"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "content-area"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "loading-state"
    }, "Loading..."))));
  }

  // Show setup wizard if needed
  if (showSetup) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "wp-seo-pilot-admin"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_pages_Setup__WEBPACK_IMPORTED_MODULE_15__["default"], {
      onComplete: handleSetupComplete,
      onSkip: handleSetupSkip
    }));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "wp-seo-pilot-admin"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "wp-seo-pilot-shell"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_Header__WEBPACK_IMPORTED_MODULE_2__["default"], {
    currentView: currentView,
    onNavigate: handleNavigate
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "content-area"
  }, renderView())));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (App);

/***/ },

/***/ "./src-v2/assistants/AssistantChat.js"
/*!********************************************!*\
  !*** ./src-v2/assistants/AssistantChat.js ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _AssistantProvider__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AssistantProvider */ "./src-v2/assistants/AssistantProvider.js");
/* harmony import */ var _AssistantMessage__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./AssistantMessage */ "./src-v2/assistants/AssistantMessage.js");
/* harmony import */ var _AssistantTyping__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./AssistantTyping */ "./src-v2/assistants/AssistantTyping.js");






/**
 * Main chat interface component.
 */
const AssistantChat = ({
  suggestedPrompts = []
}) => {
  const {
    messages,
    isLoading,
    error,
    sendMessage,
    executeAction
  } = (0,_AssistantProvider__WEBPACK_IMPORTED_MODULE_2__.useAssistant)();
  const [input, setInput] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const messagesEndRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  const inputRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);

  // Auto-scroll to bottom when new messages arrive
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    messagesEndRef.current?.scrollIntoView({
      behavior: 'smooth'
    });
  }, [messages, isLoading]);

  // Focus input on mount
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    inputRef.current?.focus();
  }, []);
  const handleSubmit = e => {
    e.preventDefault();
    if (input.trim() && !isLoading) {
      sendMessage(input);
      setInput('');
    }
  };
  const handleSuggestedPrompt = prompt => {
    if (!isLoading) {
      sendMessage(prompt);
    }
  };
  const handleAction = actionId => {
    if (!isLoading) {
      executeAction(actionId);
    }
  };
  const handleKeyDown = e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSubmit(e);
    }
  };
  const showSuggestions = messages.length <= 1 && suggestedPrompts.length > 0;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-chat"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-chat__messages"
  }, messages.map(message => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_AssistantMessage__WEBPACK_IMPORTED_MODULE_3__["default"], {
    key: message.id,
    message: message.content,
    isUser: message.role === 'user',
    actions: message.actions,
    onAction: handleAction
  })), isLoading && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_AssistantTyping__WEBPACK_IMPORTED_MODULE_4__["default"], null), error && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-chat__error"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "10"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 8v4m0 4h.01"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, error)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ref: messagesEndRef
  })), showSuggestions && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-chat__suggestions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "assistant-chat__suggestions-label"
  }, "Try asking:"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-chat__suggestions-list"
  }, suggestedPrompts.map((prompt, index) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: index,
    type: "button",
    className: "assistant-chat__suggestion",
    onClick: () => handleSuggestedPrompt(prompt),
    disabled: isLoading
  }, prompt)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("form", {
    className: "assistant-chat__input-form",
    onSubmit: handleSubmit
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-chat__input-wrapper"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    ref: inputRef,
    className: "assistant-chat__input",
    value: input,
    onChange: e => setInput(e.target.value),
    onKeyDown: handleKeyDown,
    placeholder: "Type your message...",
    disabled: isLoading,
    rows: 1
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "submit",
    className: "assistant-chat__send-btn",
    disabled: !input.trim() || isLoading
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"
  }))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AssistantChat);

/***/ },

/***/ "./src-v2/assistants/AssistantMessage.js"
/*!***********************************************!*\
  !*** ./src-v2/assistants/AssistantMessage.js ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

/**
 * Single message component for assistant chat.
 */
const AssistantMessage = ({
  message,
  isUser,
  actions,
  onAction
}) => {
  // Simple markdown-like formatting (bold, lists)
  const formatMessage = text => {
    if (!text) return '';

    // Split into lines
    const lines = text.split('\n');
    const formatted = [];
    let inList = false;
    lines.forEach((line, index) => {
      // Bold text: **text** or __text__
      let processedLine = line.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
      processedLine = processedLine.replace(/__(.*?)__/g, '<strong>$1</strong>');

      // Check for list items
      const listMatch = line.match(/^[-*]\s+(.*)$/);
      if (listMatch) {
        if (!inList) {
          formatted.push('<ul class="assistant-message__list">');
          inList = true;
        }
        formatted.push(`<li>${processedLine.replace(/^[-*]\s+/, '')}</li>`);
      } else {
        if (inList) {
          formatted.push('</ul>');
          inList = false;
        }
        if (processedLine.trim()) {
          formatted.push(`<p>${processedLine}</p>`);
        } else if (index < lines.length - 1) {
          // Keep empty lines as breaks
          formatted.push('<br/>');
        }
      }
    });
    if (inList) {
      formatted.push('</ul>');
    }
    return formatted.join('');
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `assistant-message ${isUser ? 'assistant-message--user' : 'assistant-message--assistant'}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-message__content"
  }, isUser ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, message) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-message__text",
    dangerouslySetInnerHTML: {
      __html: formatMessage(message)
    }
  })), !isUser && actions && actions.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-message__actions"
  }, actions.map(action => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: action.id,
    type: "button",
    className: "assistant-message__action-btn",
    onClick: () => onAction && onAction(action.id)
  }, action.label))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AssistantMessage);

/***/ },

/***/ "./src-v2/assistants/AssistantProvider.js"
/*!************************************************!*\
  !*** ./src-v2/assistants/AssistantProvider.js ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AssistantProvider: () => (/* binding */ AssistantProvider),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   useAssistant: () => (/* binding */ useAssistant)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);




/**
 * Assistant Context for managing chat state.
 */
const AssistantContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createContext)(null);

/**
 * Hook to use assistant context.
 */
const useAssistant = () => {
  const context = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(AssistantContext);
  if (!context) {
    throw new Error('useAssistant must be used within an AssistantProvider');
  }
  return context;
};

/**
 * Assistant Provider component.
 */
const AssistantProvider = ({
  children,
  assistantId,
  initialMessage = ''
}) => {
  const [messages, setMessages] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(() => {
    if (initialMessage) {
      return [{
        id: 'initial',
        role: 'assistant',
        content: initialMessage,
        actions: []
      }];
    }
    return [];
  });
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);

  /**
   * Send a message to the assistant.
   */
  const sendMessage = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async (content, context = {}) => {
    if (!content.trim() || isLoading) return;

    // Add user message
    const userMessage = {
      id: `user-${Date.now()}`,
      role: 'user',
      content: content.trim()
    };
    setMessages(prev => [...prev, userMessage]);
    setIsLoading(true);
    setError(null);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/assistants/chat',
        method: 'POST',
        data: {
          assistant: assistantId,
          message: content.trim(),
          context
        }
      });
      if (response.success) {
        const assistantMessage = {
          id: `assistant-${Date.now()}`,
          role: 'assistant',
          content: response.data.message,
          actions: response.data.actions || [],
          data: response.data.data
        };
        setMessages(prev => [...prev, assistantMessage]);
      } else {
        setError(response.message || 'Failed to get response');
      }
    } catch (err) {
      setError(err.message || 'An error occurred');
    } finally {
      setIsLoading(false);
    }
  }, [assistantId, isLoading]);

  /**
   * Execute an action.
   */
  const executeAction = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async (actionId, context = {}) => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/assistants/action',
        method: 'POST',
        data: {
          assistant: assistantId,
          action: actionId,
          context
        }
      });
      if (response.success) {
        const assistantMessage = {
          id: `assistant-${Date.now()}`,
          role: 'assistant',
          content: response.data.message,
          actions: response.data.actions || [],
          data: response.data.data
        };
        setMessages(prev => [...prev, assistantMessage]);
      } else {
        setError(response.message || 'Failed to execute action');
      }
    } catch (err) {
      setError(err.message || 'An error occurred');
    } finally {
      setIsLoading(false);
    }
  }, [assistantId]);

  /**
   * Clear chat history.
   */
  const clearChat = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(() => {
    setMessages(initialMessage ? [{
      id: 'initial',
      role: 'assistant',
      content: initialMessage,
      actions: []
    }] : []);
    setError(null);
  }, [initialMessage]);
  const value = {
    messages,
    isLoading,
    error,
    sendMessage,
    executeAction,
    clearChat,
    assistantId
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(AssistantContext.Provider, {
    value: value
  }, children);
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AssistantProvider);

/***/ },

/***/ "./src-v2/assistants/AssistantTyping.js"
/*!**********************************************!*\
  !*** ./src-v2/assistants/AssistantTyping.js ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

/**
 * Typing indicator component for assistant chat.
 */
const AssistantTyping = () => {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-typing"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-typing__dot"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-typing__dot"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-typing__dot"
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AssistantTyping);

/***/ },

/***/ "./src-v2/assistants/agents/GeneralSEO.js"
/*!************************************************!*\
  !*** ./src-v2/assistants/agents/GeneralSEO.js ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

/**
 * General SEO Assistant configuration.
 */
const GeneralSEO = {
  id: 'general-seo',
  name: 'SEO Assistant',
  description: 'Your helpful SEO buddy for all things search optimization.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "11",
    cy: "11",
    r: "8"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 21l-4.35-4.35"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M11 8v6M8 11h6"
  })),
  color: '#2271b1',
  initialMessage: "Hey! I'm your SEO assistant. Ask me about meta tags, keywords, content optimization, or anything SEO-related.",
  suggestedPrompts: ['How do I write a good meta description?', 'What makes a title tag effective?', 'Help me find keywords for my blog post', 'What are internal links and why do they matter?']
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (GeneralSEO);

/***/ },

/***/ "./src-v2/assistants/agents/SEOReporter.js"
/*!*************************************************!*\
  !*** ./src-v2/assistants/agents/SEOReporter.js ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

/**
 * SEO Reporter Assistant configuration.
 */
const SEOReporter = {
  id: 'seo-reporter',
  name: 'SEO Reporter',
  description: 'Your weekly SEO buddy that gives you the rundown on your site.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "9",
    y: "3",
    width: "6",
    height: "4",
    rx: "1"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 12h6M9 16h6"
  })),
  color: '#00a32a',
  initialMessage: "Hey! I can give you a quick rundown of your site's SEO health. Want me to take a look?",
  suggestedPrompts: ['Give me a quick SEO report', 'What SEO issues should I fix first?', 'Check my meta titles and descriptions', 'Find posts missing SEO data']
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SEOReporter);

/***/ },

/***/ "./src-v2/assistants/agents/index.js"
/*!*******************************************!*\
  !*** ./src-v2/assistants/agents/index.js ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   GeneralSEO: () => (/* reexport safe */ _GeneralSEO__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   SEOReporter: () => (/* reexport safe */ _SEOReporter__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   assistants: () => (/* binding */ assistants),
/* harmony export */   getAssistantById: () => (/* binding */ getAssistantById)
/* harmony export */ });
/* harmony import */ var _GeneralSEO__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./GeneralSEO */ "./src-v2/assistants/agents/GeneralSEO.js");
/* harmony import */ var _SEOReporter__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SEOReporter */ "./src-v2/assistants/agents/SEOReporter.js");



/**
 * All available assistants.
 */
const assistants = [_GeneralSEO__WEBPACK_IMPORTED_MODULE_0__["default"], _SEOReporter__WEBPACK_IMPORTED_MODULE_1__["default"]];

/**
 * Get assistant by ID.
 */
const getAssistantById = id => {
  return assistants.find(a => a.id === id);
};


/***/ },

/***/ "./src-v2/assistants/index.js"
/*!************************************!*\
  !*** ./src-v2/assistants/index.js ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AssistantChat: () => (/* reexport safe */ _AssistantChat__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   AssistantMessage: () => (/* reexport safe */ _AssistantMessage__WEBPACK_IMPORTED_MODULE_2__["default"]),
/* harmony export */   AssistantProvider: () => (/* reexport safe */ _AssistantProvider__WEBPACK_IMPORTED_MODULE_0__.AssistantProvider),
/* harmony export */   AssistantTyping: () => (/* reexport safe */ _AssistantTyping__WEBPACK_IMPORTED_MODULE_3__["default"]),
/* harmony export */   GeneralSEO: () => (/* reexport safe */ _agents__WEBPACK_IMPORTED_MODULE_4__.GeneralSEO),
/* harmony export */   SEOReporter: () => (/* reexport safe */ _agents__WEBPACK_IMPORTED_MODULE_4__.SEOReporter),
/* harmony export */   assistants: () => (/* reexport safe */ _agents__WEBPACK_IMPORTED_MODULE_4__.assistants),
/* harmony export */   getAssistantById: () => (/* reexport safe */ _agents__WEBPACK_IMPORTED_MODULE_4__.getAssistantById),
/* harmony export */   useAssistant: () => (/* reexport safe */ _AssistantProvider__WEBPACK_IMPORTED_MODULE_0__.useAssistant)
/* harmony export */ });
/* harmony import */ var _AssistantProvider__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AssistantProvider */ "./src-v2/assistants/AssistantProvider.js");
/* harmony import */ var _AssistantChat__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AssistantChat */ "./src-v2/assistants/AssistantChat.js");
/* harmony import */ var _AssistantMessage__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AssistantMessage */ "./src-v2/assistants/AssistantMessage.js");
/* harmony import */ var _AssistantTyping__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./AssistantTyping */ "./src-v2/assistants/AssistantTyping.js");
/* harmony import */ var _agents__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./agents */ "./src-v2/assistants/agents/index.js");






/***/ },

/***/ "./src-v2/components/AiGenerateModal.js"
/*!**********************************************!*\
  !*** ./src-v2/components/AiGenerateModal.js ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);

/**
 * AI Generate Modal Component
 *
 * Modal for generating SEO content (titles, descriptions) with AI
 * Supports including template variable context in the generation prompt
 */




// Get AI status from global settings
const globalSettings = window?.wpseopilotV2Settings || {};
const aiEnabled = globalSettings.aiEnabled || false;
const aiProvider = globalSettings.aiProvider || 'none';
const aiPilot = globalSettings.aiPilot || null;
const AiGenerateModal = ({
  isOpen,
  onClose,
  onGenerate,
  fieldType = 'title',
  // 'title' or 'description'
  currentValue = '',
  placeholder = '',
  variableValues = {},
  context = {} // Additional context like post type, taxonomy name, etc.
}) => {
  const [isGenerating, setIsGenerating] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [includeVariables, setIncludeVariables] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [customPrompt, setCustomPrompt] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [generatedResult, setGeneratedResult] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);

  // Build context string from variable values
  const buildContextString = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(() => {
    if (!includeVariables) return '';
    const contextParts = [];

    // Add context info
    if (context.type) {
      contextParts.push(`Content type: ${context.type}`);
    }
    if (context.name) {
      contextParts.push(`Name: ${context.name}`);
    }

    // Add variable values
    const relevantVars = Object.entries(variableValues).filter(([key, value]) => value && typeof value === 'string').map(([key, value]) => `${key.replace(/_/g, ' ')}: ${value}`);
    if (relevantVars.length > 0) {
      contextParts.push('Available data:');
      contextParts.push(...relevantVars);
    }
    return contextParts.join('\n');
  }, [includeVariables, variableValues, context]);
  const handleGenerate = async () => {
    setIsGenerating(true);
    setError(null);
    setGeneratedResult(null);
    try {
      // Build the content for AI
      let content = '';
      if (customPrompt) {
        content = customPrompt;
      } else {
        content = buildContextString() || 'Generate SEO metadata for a website.';
      }

      // Add field-specific instructions
      if (fieldType === 'title') {
        content += '\n\nGenerate an SEO-optimized title (max 60 characters).';
      } else {
        content += '\n\nGenerate an SEO-optimized meta description (max 155 characters).';
      }
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/ai/generate',
        method: 'POST',
        data: {
          content,
          type: fieldType
        }
      });
      if (response.success && response.data) {
        const result = fieldType === 'title' ? response.data.title : response.data.description;
        setGeneratedResult(result);
      } else {
        setError(response.message || 'Failed to generate content');
      }
    } catch (err) {
      setError(err.message || 'An error occurred during generation');
    } finally {
      setIsGenerating(false);
    }
  };
  const handleApply = () => {
    if (generatedResult) {
      onGenerate(generatedResult);
      handleClose();
    }
  };
  const handleClose = () => {
    setGeneratedResult(null);
    setError(null);
    setCustomPrompt('');
    onClose();
  };
  if (!isOpen) return null;

  // Show configuration notice if AI is not enabled
  if (!aiEnabled) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "ai-generate-modal-overlay",
      onClick: handleClose
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "ai-generate-modal ai-generate-modal--notice",
      onClick: e => e.stopPropagation()
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "ai-generate-modal__header"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      width: "18",
      height: "18",
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      strokeWidth: "2"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
      cx: "12",
      cy: "12",
      r: "10"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M12 8v4m0 4h.01"
    })), "AI Not Configured"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "ai-generate-modal__close",
      onClick: handleClose,
      "aria-label": "Close"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      width: "20",
      height: "20",
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      strokeWidth: "2"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M18 6L6 18M6 6l12 12"
    })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "ai-generate-modal__body"
    }, aiPilot?.installed ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "ai-generate-modal__notice ai-generate-modal__notice--warning"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "ai-generate-modal__notice-icon"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      width: "24",
      height: "24",
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      strokeWidth: "2"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
    }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "ai-generate-modal__notice-content"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "WP AI Pilot Needs Configuration"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "WP AI Pilot is installed but not yet configured. Add an API key to enable AI-powered SEO suggestions."))) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "ai-generate-modal__notice ai-generate-modal__notice--info"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "ai-generate-modal__notice-icon"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      width: "24",
      height: "24",
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      strokeWidth: "2"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
    }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "ai-generate-modal__notice-content"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Enhance with WP AI Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Install WP AI Pilot to unlock AI-powered title and meta description generation.")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "ai-generate-modal__footer"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "button ghost",
      onClick: handleClose
    }, "Cancel"), aiPilot?.installed ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: aiPilot.settingsUrl || 'admin.php?page=wp-ai-pilot',
      className: "button primary"
    }, "Configure WP AI Pilot") : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: "plugin-install.php?s=wp+ai+pilot&tab=search",
      className: "button primary"
    }, "Install WP AI Pilot"))));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal-overlay",
    onClick: handleClose
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal",
    onClick: e => e.stopPropagation()
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "18",
    height: "18",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 3v1m0 16v1m-9-9h1m16 0h1m-2.636-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "4"
  })), "Generate ", fieldType === 'title' ? 'Title' : 'Description', " with AI"), aiProvider === 'wp-ai-pilot' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ai-generate-modal__badge"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "12",
    height: "12",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
  })), "WP AI Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "ai-generate-modal__close",
    onClick: handleClose,
    "aria-label": "Close"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M18 6L6 18M6 6l12 12"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal__body"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal__option"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "ai-generate-modal__checkbox"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: includeVariables,
    onChange: e => setIncludeVariables(e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ai-generate-modal__checkbox-mark"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ai-generate-modal__checkbox-label"
  }, "Include template variables as context")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "ai-generate-modal__help"
  }, "Sends available data (site name, post type info, etc.) to help AI generate better content.")), includeVariables && Object.keys(variableValues).length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal__context-preview"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Context that will be sent:"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal__context-box"
  }, Object.entries(variableValues).filter(([key, value]) => value && typeof value === 'string').map(([key, value]) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: key,
    className: "ai-generate-modal__context-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ai-generate-modal__context-key"
  }, key, ":"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ai-generate-modal__context-value"
  }, value))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal__field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "ai-custom-prompt"
  }, "Custom instructions (optional)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    id: "ai-custom-prompt",
    value: customPrompt,
    onChange: e => setCustomPrompt(e.target.value),
    placeholder: `e.g., "Focus on ${fieldType === 'title' ? 'including the brand name' : 'highlighting key benefits'}" or "Use a professional tone"`,
    rows: 3
  })), error && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal__error"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "10"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 8v4m0 4h.01"
  })), error), generatedResult && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal__result"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Generated ", fieldType === 'title' ? 'title' : 'description', ":"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal__result-box"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, generatedResult), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ai-generate-modal__char-count"
  }, generatedResult.length, " characters")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-generate-modal__footer"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: handleClose
  }, "Cancel"), generatedResult ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button",
    onClick: handleGenerate,
    disabled: isGenerating
  }, "Regenerate"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleApply
  }, "Apply")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleGenerate,
    disabled: isGenerating
  }, isGenerating ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ai-generate-modal__spinner"
  }), "Generating...") : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 3v1m0 16v1m-9-9h1m16 0h1m-2.636-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "4"
  })), "Generate")))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AiGenerateModal);

/***/ },

/***/ "./src-v2/components/AnalyticsNotice.js"
/*!**********************************************!*\
  !*** ./src-v2/components/AnalyticsNotice.js ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);

/**
 * Analytics Privacy Notice Component
 *
 * Displays information about what data is collected and how it's used.
 */


const AnalyticsNotice = ({
  isEnabled,
  onToggle
}) => {
  const [expanded, setExpanded] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "analytics-notice"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "analytics-notice__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "analytics-notice__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 16v-4"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 8h.01"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "analytics-notice__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Help Improve WP SEO Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Share anonymous usage data to help us understand which features are most valuable and improve the plugin for everyone.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: isEnabled,
    onChange: e => onToggle(e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "analytics-notice__expand",
    onClick: () => setExpanded(!expanded)
  }, expanded ? 'Hide details' : 'What data is collected?', (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    style: {
      transform: expanded ? 'rotate(180deg)' : 'none'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "6 9 12 15 18 9"
  }))), expanded && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "analytics-notice__details"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "analytics-privacy-info"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, "What We Collect"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "20 6 9 17 4 12"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Feature usage (e.g., \"redirect created\", \"AI title generated\")")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "20 6 9 17 4 12"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Plugin version number")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "20 6 9 17 4 12"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Pages visited within the plugin")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "20 6 9 17 4 12"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Anonymized site identifier (hashed URL)"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, "What We Never Collect"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
    className: "analytics-privacy-info__never"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "18",
    y1: "6",
    x2: "6",
    y2: "18"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "6",
    y1: "6",
    x2: "18",
    y2: "18"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Personal information (names, emails, IP addresses)")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "18",
    y1: "6",
    x2: "6",
    y2: "18"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "6",
    y1: "6",
    x2: "18",
    y2: "18"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Your content (posts, pages, meta data)")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "18",
    y1: "6",
    x2: "6",
    y2: "18"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "6",
    y1: "6",
    x2: "18",
    y2: "18"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "API keys or credentials")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "18",
    y1: "6",
    x2: "6",
    y2: "18"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "6",
    y1: "6",
    x2: "18",
    y2: "18"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Your site's URL or domain name"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, "Privacy Measures"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "analytics-privacy-info__measures"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "privacy-measure"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "privacy-measure__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "3",
    y: "11",
    width: "18",
    height: "11",
    rx: "2",
    ry: "2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M7 11V7a5 5 0 0 1 10 0v4"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "No Cookies"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "We don't use any tracking cookies"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "privacy-measure"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "privacy-measure__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Admin Only"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Only tracks within plugin admin pages"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "privacy-measure"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "privacy-measure__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "10"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "4.93",
    y1: "4.93",
    x2: "19.07",
    y2: "19.07"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Opt-out Anytime"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Disable tracking at any time"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "privacy-measure"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "privacy-measure__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Self-Hosted"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Analytics on our own Matomo instance")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "analytics-privacy-info__footer"
  }, "This data helps us prioritize features, fix common issues, and understand how the plugin is used in real-world scenarios. Thank you for helping us improve!"))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AnalyticsNotice);

/***/ },

/***/ "./src-v2/components/Header.js"
/*!*************************************!*\
  !*** ./src-v2/components/Header.js ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const navItems = [{
  id: 'dashboard',
  label: 'Dashboard'
}, {
  id: 'search-appearance',
  label: 'Search Appearance'
}, {
  id: 'sitemap',
  label: 'Sitemap'
}, {
  id: 'tools',
  label: 'Tools'
}, {
  id: 'settings',
  label: 'Settings'
}, {
  id: 'more',
  label: 'More'
}];
const Header = ({
  currentView,
  onNavigate
}) => {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("header", {
    className: "top-bar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "brand"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "brand-icon",
    "aria-hidden": "true"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    role: "img",
    focusable: "false",
    preserveAspectRatio: "xMidYMid meet"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "brand-name"
  }, "WP SEO Pilot")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("nav", {
    className: "main-nav",
    "aria-label": "Primary"
  }, navItems.map(item => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: item.id,
    type: "button",
    className: `nav-tab ${currentView === item.id ? 'is-active' : ''}`,
    "aria-current": currentView === item.id ? 'page' : undefined,
    onClick: () => onNavigate(item.id)
  }, item.label))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "nav-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: "icon-button",
    href: "https://github.com/jhd3197/WP-SEO-Pilot",
    target: "_blank",
    rel: "noreferrer",
    "aria-label": "Open GitHub repository"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    role: "img",
    focusable: "false"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2C6.5 2 2 6.6 2 12.3c0 4.6 2.9 8.5 6.9 9.9.5.1.7-.2.7-.5v-1.9c-2.8.6-3.3-1.2-3.3-1.2-.5-1.2-1.2-1.5-1.2-1.5-1-.7.1-.7.1-.7 1.1.1 1.7 1.2 1.7 1.2 1 .1.7 1.7 2.6 1.2.1-.8.4-1.2.7-1.5-2.2-.2-4.5-1.2-4.5-5.2 0-1.1.4-2 1-2.7-.1-.2-.4-1.3.1-2.7 0 0 .8-.2 2.7 1a9.2 9.2 0 0 1 4.9 0c1.9-1.2 2.7-1 2.7-1 .5 1.4.2 2.5.1 2.7.6.7 1 1.6 1 2.7 0 4-2.3 5-4.5 5.2.4.3.8 1 .8 2.1v3c0 .3.2.6.7.5 4-1.4 6.9-5.3 6.9-9.9C22 6.6 17.5 2 12 2z"
  })))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Header);

/***/ },

/***/ "./src-v2/components/SearchPreview.js"
/*!********************************************!*\
  !*** ./src-v2/components/SearchPreview.js ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

/**
 * Search Preview Component
 *
 * Displays a preview of how content will appear in search results.
 * Style inspired by search engine result pages.
 */

const SearchPreview = ({
  title = '',
  description = '',
  url = '',
  domain = '',
  favicon = '',
  maxTitleLength = 60,
  maxDescriptionLength = 155
}) => {
  const titleLength = title.length;
  const descriptionLength = description.length;
  const isTitleOverLimit = titleLength > maxTitleLength;
  const isDescriptionOverLimit = descriptionLength > maxDescriptionLength;

  // Truncate for display if over limit
  const displayTitle = isTitleOverLimit ? title.substring(0, maxTitleLength) + '...' : title;
  const displayDescription = isDescriptionOverLimit ? description.substring(0, maxDescriptionLength) + '...' : description;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "search-preview"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "search-preview__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "search-preview__label"
  }, "Search Result Preview")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "search-preview__body"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "search-preview__url"
  }, favicon ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: favicon,
    alt: "",
    className: "search-preview__favicon"
  }) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "search-preview__favicon-placeholder"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "search-preview__domain"
  }, domain || url)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "search-preview__title"
  }, displayTitle || 'Page Title'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "search-preview__description"
  }, displayDescription || 'Meta description will appear here...')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "search-preview__footer"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `search-preview__counter ${isTitleOverLimit ? 'over-limit' : ''}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, titleLength), " / ", maxTitleLength, " chars (title)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `search-preview__counter ${isDescriptionOverLimit ? 'over-limit' : ''}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, descriptionLength), " / ", maxDescriptionLength, " chars (description)")));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SearchPreview);

/***/ },

/***/ "./src-v2/components/SubTabs.js"
/*!**************************************!*\
  !*** ./src-v2/components/SubTabs.js ***!
  \**************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const SubTabs = ({
  tabs,
  activeTab,
  onChange,
  ariaLabel
}) => {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "sub-tabs",
    role: "tablist",
    "aria-label": ariaLabel
  }, tabs.map(tab => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: tab.id,
    type: "button",
    role: "tab",
    className: `sub-tab ${activeTab === tab.id ? 'is-active' : ''}`,
    "aria-selected": activeTab === tab.id,
    onClick: () => onChange(tab.id)
  }, tab.label)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SubTabs);

/***/ },

/***/ "./src-v2/components/TemplateInput.js"
/*!********************************************!*\
  !*** ./src-v2/components/TemplateInput.js ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _VariablePicker__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./VariablePicker */ "./src-v2/components/VariablePicker.js");

/**
 * Template Input Component
 *
 * An input/textarea with:
 * - Syntax highlighting for variables (colored {{ }} brackets)
 * - Hover preview showing rendered values
 * - Floating action icons (Variables, AI)
 */




// Variable type color mapping
const variableColors = {
  // Global variables - blue
  site_title: 'global',
  tagline: 'global',
  site_url: 'global',
  separator: 'separator',
  current_year: 'global',
  current_month: 'global',
  current_day: 'global',
  // Post variables - violet
  post_title: 'post',
  post_excerpt: 'post',
  post_date: 'post',
  post_modified: 'post',
  post_author: 'post',
  post_id: 'post',
  post_content: 'post',
  // Taxonomy variables - green
  term_title: 'taxonomy',
  term_description: 'taxonomy',
  term_count: 'taxonomy',
  // Author variables - orange
  author_name: 'author',
  author_bio: 'author',
  // Archive variables - teal
  archive_title: 'archive',
  search_query: 'archive',
  page_number: 'archive'
};

// Extract base tag from variable (handles modifiers like "post_title | upper")
const getBaseTag = fullTag => {
  const pipeIndex = fullTag.indexOf('|');
  if (pipeIndex > -1) {
    return fullTag.substring(0, pipeIndex).trim();
  }
  return fullTag.trim();
};
const getVariableType = tag => {
  const baseTag = getBaseTag(tag);
  return variableColors[baseTag] || 'global';
};
const TemplateInput = ({
  value = '',
  onChange,
  placeholder = '',
  variables = {},
  variableValues = {},
  context = 'global',
  multiline = false,
  maxLength = null,
  label = '',
  helpText = '',
  id,
  disabled = false,
  onAiClick = null,
  showAiButton = true,
  aiEnabled = true,
  aiDisabledReason = null
}) => {
  const inputRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  const highlightRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  const [isFocused, setIsFocused] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [showVariablePicker, setShowVariablePicker] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [hoveredVariable, setHoveredVariable] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);

  // Sync scroll position between input and highlight overlay
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    const input = inputRef.current;
    const highlight = highlightRef.current;
    if (!input || !highlight) return;
    const syncScroll = () => {
      highlight.scrollTop = input.scrollTop;
      highlight.scrollLeft = input.scrollLeft;
    };
    input.addEventListener('scroll', syncScroll);
    return () => input.removeEventListener('scroll', syncScroll);
  }, []);

  // Parse template into parts with syntax highlighting
  const renderHighlighted = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useMemo)(() => {
    if (!value) return [];
    const parts = [];
    let lastIndex = 0;
    const regex = /\{\{([^}]+)\}\}/g;
    let match;
    while ((match = regex.exec(value)) !== null) {
      // Add text before the variable
      if (match.index > lastIndex) {
        parts.push({
          type: 'text',
          content: value.slice(lastIndex, match.index)
        });
      }
      const fullTag = match[1].trim();
      const baseTag = getBaseTag(fullTag);
      const varType = getVariableType(baseTag);
      const previewValue = variableValues[baseTag] || variableValues[`{{${baseTag}}}`];
      parts.push({
        type: 'variable',
        fullTag: fullTag,
        baseTag: baseTag,
        raw: match[0],
        preview: previewValue || baseTag,
        varType: varType
      });
      lastIndex = regex.lastIndex;
    }

    // Add remaining text
    if (lastIndex < value.length) {
      parts.push({
        type: 'text',
        content: value.slice(lastIndex)
      });
    }
    return parts;
  }, [value, variableValues]);

  // Insert variable at cursor position
  const insertVariable = variableTag => {
    const input = inputRef.current;
    if (!input) {
      onChange(value + variableTag);
      return;
    }
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const newValue = value.slice(0, start) + variableTag + value.slice(end);
    onChange(newValue);
    setShowVariablePicker(false);
    requestAnimationFrame(() => {
      const newPos = start + variableTag.length;
      input.setSelectionRange(newPos, newPos);
      input.focus();
    });
  };
  const charCount = value.length;
  const isOverLimit = maxLength && charCount > maxLength;
  const InputComponent = multiline ? 'textarea' : 'input';
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `template-input-v2 ${isFocused ? 'is-focused' : ''} ${disabled ? 'is-disabled' : ''}`
  }, label && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "template-input-v2__label",
    htmlFor: id
  }, label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "template-input-v2__container"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ref: highlightRef,
    className: `template-input-v2__highlight ${multiline ? 'multiline' : ''}`,
    "aria-hidden": "true"
  }, renderHighlighted.map((part, index) => part.type === 'variable' ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    key: index,
    className: `template-input-v2__var template-input-v2__var--${part.varType}`,
    onMouseEnter: () => setHoveredVariable({
      ...part,
      index
    }),
    onMouseLeave: () => setHoveredVariable(null)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "template-input-v2__bracket"
  }, '{'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "template-input-v2__bracket"
  }, '{'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "template-input-v2__tag"
  }, part.fullTag), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "template-input-v2__bracket"
  }, '}'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "template-input-v2__bracket"
  }, '}'), hoveredVariable?.index === index && part.preview && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "template-input-v2__tooltip"
  }, part.preview)) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    key: index
  }, part.content)), !value && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "template-input-v2__placeholder"
  }, placeholder)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(InputComponent, {
    ref: inputRef,
    id: id,
    type: multiline ? undefined : 'text',
    className: `template-input-v2__field ${multiline ? 'multiline' : ''}`,
    value: value,
    onChange: e => onChange(e.target.value),
    onFocus: () => setIsFocused(true),
    onBlur: () => setIsFocused(false),
    placeholder: "",
    disabled: disabled,
    rows: multiline ? 3 : undefined
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "template-input-v2__actions"
  }, showAiButton && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "template-input-v2__ai-wrapper"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: `template-input-v2__action-btn template-input-v2__action-btn--ai ${!aiEnabled ? 'is-disabled' : ''}`,
    onClick: aiEnabled ? onAiClick : undefined,
    disabled: disabled || !aiEnabled || !onAiClick,
    title: aiEnabled ? 'Generate with AI' : aiDisabledReason || 'Configure WP AI Pilot to enable AI'
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "14",
    height: "14",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round",
    strokeLinejoin: "round"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 3v1m0 16v1m-9-9h1m16 0h1m-2.636-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "4"
  }))), !aiEnabled && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "template-input-v2__ai-tooltip"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "AI requires WP AI Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "admin.php?page=wp-ai-pilot",
    className: "template-input-v2__ai-tooltip-link"
  }, "Configure"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_VariablePicker__WEBPACK_IMPORTED_MODULE_2__["default"], {
    variables: variables,
    context: context,
    onSelect: insertVariable,
    disabled: disabled,
    isOpen: showVariablePicker,
    onToggle: () => setShowVariablePicker(!showVariablePicker),
    onClose: () => setShowVariablePicker(false),
    compact: true
  }))), (helpText || maxLength) && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "template-input-v2__footer"
  }, helpText && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "template-input-v2__help"
  }, helpText), maxLength && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `template-input-v2__counter ${isOverLimit ? 'over-limit' : ''}`
  }, charCount, "/", maxLength)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TemplateInput);

/***/ },

/***/ "./src-v2/components/VariablePicker.js"
/*!*********************************************!*\
  !*** ./src-v2/components/VariablePicker.js ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);

/**
 * Variable Picker Component
 *
 * A modern dropdown for inserting template variables.
 * Supports compact mode for inline use within inputs.
 */


const VariablePicker = ({
  variables = {},
  onSelect,
  context = 'global',
  buttonLabel = 'Variables',
  disabled = false,
  compact = false,
  // Compact mode for inline buttons
  isOpen: controlledOpen,
  // Controlled open state
  onToggle,
  // For controlled mode
  onClose // For controlled mode
}) => {
  const [internalOpen, setInternalOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [searchTerm, setSearchTerm] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const containerRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);

  // Use controlled or internal state
  const isOpen = controlledOpen !== undefined ? controlledOpen : internalOpen;
  const setIsOpen = value => {
    if (controlledOpen !== undefined) {
      if (value) {
        onToggle?.();
      } else {
        onClose?.();
      }
    } else {
      setInternalOpen(value);
    }
  };

  // Close on outside click
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    const handleClickOutside = e => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setIsOpen(false);
      }
    };
    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [isOpen, controlledOpen]);

  // Filter variables by context and search term
  const getFilteredVariables = () => {
    const filtered = {};
    const contextGroups = {
      global: ['global'],
      post: ['global', 'post'],
      taxonomy: ['global', 'taxonomy'],
      archive: ['global', 'archive', 'author'],
      author: ['global', 'author'],
      date: ['global', 'archive'],
      search: ['global'],
      '404': ['global']
    };
    const allowedGroups = contextGroups[context] || ['global'];
    Object.entries(variables).forEach(([groupKey, group]) => {
      if (!allowedGroups.includes(groupKey)) return;
      const filteredVars = (group.vars || []).filter(v => {
        if (!searchTerm) return true;
        const term = searchTerm.toLowerCase();
        return v.tag.toLowerCase().includes(term) || v.label.toLowerCase().includes(term) || v.desc && v.desc.toLowerCase().includes(term);
      });
      if (filteredVars.length > 0) {
        filtered[groupKey] = {
          ...group,
          vars: filteredVars
        };
      }
    });
    return filtered;
  };
  const handleSelect = variable => {
    if (onSelect) {
      onSelect(`{{${variable.tag}}}`);
    }
    setIsOpen(false);
    setSearchTerm('');
  };
  const handleToggle = () => {
    if (controlledOpen !== undefined) {
      onToggle?.();
    } else {
      setInternalOpen(!internalOpen);
    }
  };
  const filteredVariables = isOpen ? getFilteredVariables() : {};

  // Compact mode - just an icon button
  if (compact) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "variable-picker variable-picker--compact",
      ref: containerRef
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "template-input-v2__action-btn template-input-v2__action-btn--vars",
      onClick: handleToggle,
      disabled: disabled,
      title: "Insert variable"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      width: "14",
      height: "14",
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      strokeWidth: "2",
      strokeLinecap: "round",
      strokeLinejoin: "round"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M4 7h3a1 1 0 0 0 1-1V3"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M20 7h-3a1 1 0 0 1-1-1V3"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M4 17h3a1 1 0 0 1 1 1v3"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M20 17h-3a1 1 0 0 0-1 1v3"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M9 12h6"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M12 9v6"
    }))), isOpen && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "variable-picker__dropdown"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "variable-picker__search"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      type: "text",
      placeholder: "Search variables...",
      value: searchTerm,
      onChange: e => setSearchTerm(e.target.value),
      autoFocus: true
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "variable-picker__groups"
    }, Object.entries(filteredVariables).map(([groupKey, group]) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      key: groupKey,
      className: "variable-picker__group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: `variable-picker__group-label variable-picker__group-label--${groupKey}`
    }, group.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "variable-picker__items"
    }, group.vars.map(variable => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      key: variable.tag,
      type: "button",
      className: "variable-picker__item",
      onClick: () => handleSelect(variable)
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "variable-picker__item-header"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", {
      className: `variable-picker__tag variable-picker__tag--${groupKey}`
    }, variable.tag), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "variable-picker__label"
    }, variable.label)), variable.preview && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "variable-picker__preview"
    }, variable.preview)))))), Object.keys(filteredVariables).length === 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "variable-picker__empty"
    }, "No variables found"))));
  }

  // Full mode - button with text
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "variable-picker",
    ref: containerRef
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "variable-picker__trigger",
    onClick: handleToggle,
    disabled: disabled,
    title: "Insert variable"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 20 20",
    fill: "currentColor",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    fillRule: "evenodd",
    d: "M4.5 2A2.5 2.5 0 002 4.5v3.879a2.5 2.5 0 00.732 1.767l7.5 7.5a2.5 2.5 0 003.536 0l3.878-3.878a2.5 2.5 0 000-3.536l-7.5-7.5A2.5 2.5 0 008.38 2H4.5zM5 6a1 1 0 100-2 1 1 0 000 2z",
    clipRule: "evenodd"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, buttonLabel)), isOpen && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "variable-picker__dropdown"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "variable-picker__search"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    placeholder: "Search variables...",
    value: searchTerm,
    onChange: e => setSearchTerm(e.target.value),
    autoFocus: true
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "variable-picker__groups"
  }, Object.entries(filteredVariables).map(([groupKey, group]) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: groupKey,
    className: "variable-picker__group"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `variable-picker__group-label variable-picker__group-label--${groupKey}`
  }, group.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "variable-picker__items"
  }, group.vars.map(variable => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: variable.tag,
    type: "button",
    className: "variable-picker__item",
    onClick: () => handleSelect(variable)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "variable-picker__item-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", {
    className: `variable-picker__tag variable-picker__tag--${groupKey}`
  }, variable.tag), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "variable-picker__label"
  }, variable.label)), variable.preview && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "variable-picker__preview"
  }, variable.preview)))))), Object.keys(filteredVariables).length === 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "variable-picker__empty"
  }, "No variables found"))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (VariablePicker);

/***/ },

/***/ "./src-v2/hooks/useUrlTab.js"
/*!***********************************!*\
  !*** ./src-v2/hooks/useUrlTab.js ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const getTabFromUrl = (tabs, defaultTab, paramName) => {
  if (typeof window === 'undefined') {
    return defaultTab;
  }
  const url = new URL(window.location.href);
  const tab = url.searchParams.get(paramName);
  if (tab && tabs.some(item => item.id === tab)) {
    return tab;
  }
  return defaultTab;
};
const useUrlTab = ({
  tabs,
  defaultTab,
  paramName = 'tab'
}) => {
  const [activeTab, setActiveTab] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(() => getTabFromUrl(tabs, defaultTab, paramName));
  const updateTab = tabId => {
    if (!tabs.some(item => item.id === tabId)) {
      return;
    }
    setActiveTab(tabId);
    if (typeof window === 'undefined') {
      return;
    }
    const url = new URL(window.location.href);
    url.searchParams.set(paramName, tabId);
    window.history.replaceState({}, '', url.toString());
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const handlePopState = () => {
      setActiveTab(getTabFromUrl(tabs, defaultTab, paramName));
    };
    window.addEventListener('popstate', handlePopState);
    return () => window.removeEventListener('popstate', handlePopState);
  }, [tabs, defaultTab, paramName]);
  return [activeTab, updateTab];
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useUrlTab);

/***/ },

/***/ "./src-v2/index.css"
/*!**************************!*\
  !*** ./src-v2/index.css ***!
  \**************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./src-v2/pages/AiAssistant.js"
/*!*************************************!*\
  !*** ./src-v2/pages/AiAssistant.js ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);



const AiAssistant = () => {
  // Loading states
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [resetting, setResetting] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [generating, setGenerating] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);

  // Settings state (prompts only - API keys deprecated)
  const [settings, setSettings] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    ai_prompt_system: '',
    ai_prompt_title: '',
    ai_prompt_description: ''
  });

  // API status from AI Controller (includes WP AI Pilot status)
  const [apiStatus, setApiStatus] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    configured: false,
    status: 'not_configured',
    message: 'Not configured',
    provider: 'none',
    ai_pilot: null
  });

  // Test generation
  const [testContent, setTestContent] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [generatedTitle, setGeneratedTitle] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [generatedDescription, setGeneratedDescription] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');

  // Messages
  const [message, setMessage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    type: '',
    text: ''
  });

  // Fetch data
  const fetchData = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setLoading(true);
    try {
      const [settingsRes, statusRes] = await Promise.all([_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/ai/settings'
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/ai/status'
      })]);
      if (settingsRes.success) {
        setSettings(prev => ({
          ...prev,
          ai_prompt_system: settingsRes.data.ai_prompt_system || '',
          ai_prompt_title: settingsRes.data.ai_prompt_title || '',
          ai_prompt_description: settingsRes.data.ai_prompt_description || ''
        }));
      }
      if (statusRes.success) setApiStatus(statusRes.data);
    } catch (error) {
      console.error('Failed to fetch AI settings:', error);
      setMessage({
        type: 'error',
        text: 'Failed to load AI settings.'
      });
    } finally {
      setLoading(false);
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchData();
  }, [fetchData]);

  // Save prompt settings only
  const handleSaveSettings = async () => {
    setSaving(true);
    setMessage({
      type: '',
      text: ''
    });
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/ai/settings',
        method: 'POST',
        data: {
          ai_prompt_system: settings.ai_prompt_system,
          ai_prompt_title: settings.ai_prompt_title,
          ai_prompt_description: settings.ai_prompt_description
        }
      });
      if (res.success) {
        setMessage({
          type: 'success',
          text: 'Prompt settings saved successfully!'
        });
      }
    } catch (error) {
      console.error('Failed to save settings:', error);
      setMessage({
        type: 'error',
        text: 'Failed to save settings.'
      });
    } finally {
      setSaving(false);
    }
  };

  // Reset to defaults
  const handleReset = async () => {
    if (!window.confirm('Reset prompts to defaults?')) {
      return;
    }
    setResetting(true);
    setMessage({
      type: '',
      text: ''
    });
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/ai/reset',
        method: 'POST'
      });
      if (res.success) {
        setSettings(prev => ({
          ...prev,
          ai_prompt_system: res.data.ai_prompt_system || '',
          ai_prompt_title: res.data.ai_prompt_title || '',
          ai_prompt_description: res.data.ai_prompt_description || ''
        }));
        setMessage({
          type: 'success',
          text: 'Prompts restored to defaults.'
        });
      }
    } catch (error) {
      console.error('Failed to reset settings:', error);
      setMessage({
        type: 'error',
        text: 'Failed to reset settings.'
      });
    } finally {
      setResetting(false);
    }
  };

  // Test generation
  const handleGenerate = async () => {
    if (!testContent.trim()) {
      setMessage({
        type: 'error',
        text: 'Please enter some content to analyze.'
      });
      return;
    }
    if (!apiStatus.configured) {
      setMessage({
        type: 'error',
        text: 'Please configure WP AI Pilot to enable AI generation.'
      });
      return;
    }
    setGenerating(true);
    setMessage({
      type: '',
      text: ''
    });
    setGeneratedTitle('');
    setGeneratedDescription('');
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/ai/generate',
        method: 'POST',
        data: {
          content: testContent,
          type: 'both'
        }
      });
      if (res.success) {
        setGeneratedTitle(res.data.title || '');
        setGeneratedDescription(res.data.description || '');
        setMessage({
          type: 'success',
          text: 'Generation complete!'
        });
      }
    } catch (error) {
      console.error('Failed to generate:', error);
      setMessage({
        type: 'error',
        text: error.message || 'Failed to generate content.'
      });
    } finally {
      setGenerating(false);
    }
  };

  // Get provider display name
  const getProviderName = () => {
    switch (apiStatus.provider) {
      case 'wp-ai-pilot':
        return 'WP AI Pilot';
      case 'native':
        return 'Native API Keys (Deprecated)';
      default:
        return 'Not Configured';
    }
  };
  if (loading) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page-header"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "AI Assistant"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Configure AI-powered SEO content generation."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "loading-state"
    }, "Loading AI settings..."));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "AI Assistant"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Configure AI-powered SEO content generation.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "header-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `api-status-badge ${apiStatus.configured ? 'connected' : 'disconnected'}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "status-dot"
  }), apiStatus.configured ? 'Connected' : 'Not Connected'))), message.text && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `notice-message ${message.type}`
  }, message.text), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-settings-layout"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-config-column"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-card-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "AI Provider"), apiStatus.provider === 'wp-ai-pilot' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "provider-badge provider-badge--active"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "14",
    height: "14",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
  })), "WP AI Pilot")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-card-body"
  }, apiStatus.provider === 'wp-ai-pilot' ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-provider-status ai-provider-status--connected"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-provider-status__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M22 11.08V12a10 10 0 1 1-5.93-9.14"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "22 4 12 14.01 9 11.01"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-provider-status__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Connected to WP AI Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "AI features are powered by WP AI Pilot. Manage your API keys and models there."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: apiStatus.ai_pilot?.settings_url || 'admin.php?page=wp-ai-pilot',
    className: "button primary"
  }, "Open WP AI Pilot Settings"))) : apiStatus.ai_pilot?.installed ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-provider-status ai-provider-status--warning"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-provider-status__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "10"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 8v4m0 4h.01"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-provider-status__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "WP AI Pilot Needs Configuration"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "WP AI Pilot is installed but not configured. Add an API key to enable AI features."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: apiStatus.ai_pilot?.settings_url || 'admin.php?page=wp-ai-pilot',
    className: "button primary"
  }, "Configure WP AI Pilot"))) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-provider-status ai-provider-status--info"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-provider-status__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-provider-status__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Install WP AI Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "AI features in WP SEO Pilot are now powered by WP AI Pilot. Install it to enable AI-powered title and description generation."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "plugin-install.php?s=wp+ai+pilot&tab=search",
    className: "button primary"
  }, "Install WP AI Pilot"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "deprecation-notice"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "10"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 16v-4M12 8h.01"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "API Key Management Deprecated"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Direct API key configuration has been moved to WP AI Pilot. This provides centralized AI management across all your WordPress plugins."))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-card-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Prompt Configuration"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "link-button",
    onClick: handleReset,
    disabled: resetting
  }, resetting ? 'Resetting...' : 'Reset Defaults')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-card-body"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-prompts-stack"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-prompt-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "system-prompt"
  }, "System Prompt", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "label-hint"
  }, "Base instructions for every request")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    id: "system-prompt",
    value: settings.ai_prompt_system,
    onChange: e => setSettings(prev => ({
      ...prev,
      ai_prompt_system: e.target.value
    })),
    rows: "2",
    placeholder: "You are an SEO assistant..."
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-prompts-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-prompt-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "title-prompt"
  }, "Title Prompt", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "label-hint"
  }, "How to craft titles")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    id: "title-prompt",
    value: settings.ai_prompt_title,
    onChange: e => setSettings(prev => ({
      ...prev,
      ai_prompt_title: e.target.value
    })),
    rows: "2",
    placeholder: "Write an SEO meta title..."
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-prompt-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "desc-prompt"
  }, "Description Prompt", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "label-hint"
  }, "How to craft descriptions")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    id: "desc-prompt",
    value: settings.ai_prompt_description,
    onChange: e => setSettings(prev => ({
      ...prev,
      ai_prompt_description: e.target.value
    })),
    rows: "2",
    placeholder: "Write a meta description..."
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-card-footer"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleSaveSettings,
    disabled: saving
  }, saving ? 'Saving...' : 'Save Prompt Settings')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-test-column"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-card ai-test-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-card-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Test Generation"), apiStatus.provider && apiStatus.provider !== 'none' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "provider-tag"
  }, "via ", getProviderName())), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-card-body"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-test-input"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    value: testContent,
    onChange: e => setTestContent(e.target.value),
    rows: "4",
    placeholder: "Paste content here to test AI generation. Provide at least 100 words for best results...",
    disabled: !apiStatus.configured
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleGenerate,
    disabled: generating || !apiStatus.configured
  }, generating ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "spinner"
  }), "Generating...") : 'Generate')), !apiStatus.configured && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-test-disabled-notice"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Configure WP AI Pilot to test AI generation.")), (generatedTitle || generatedDescription) && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-results"
  }, generatedTitle && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-result-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-result-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ai-result-label"
  }, "Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ai-result-count"
  }, generatedTitle.length, " chars")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-result-value"
  }, generatedTitle)), generatedDescription && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-result-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-result-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ai-result-label"
  }, "Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "ai-result-count"
  }, generatedDescription.length, " chars")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-result-value"
  }, generatedDescription))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-info-grid"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-info-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-info-icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round",
    strokeLinejoin: "round"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-info-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Unified AI Platform"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "WP AI Pilot manages AI for all your plugins in one place"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-info-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-info-icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round",
    strokeLinejoin: "round"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-info-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Privacy First"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "API keys stored locally, nothing saved externally"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-info-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-info-icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M13 2L3 14h9l-1 8 10-12h-9l1-8z",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round",
    strokeLinejoin: "round"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "ai-info-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Multiple Providers"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "OpenAI, Anthropic, Google AI, and more via WP AI Pilot")))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AiAssistant);

/***/ },

/***/ "./src-v2/pages/Assistants.js"
/*!************************************!*\
  !*** ./src-v2/pages/Assistants.js ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _assistants__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../assistants */ "./src-v2/assistants/index.js");





// Get AI status from global settings
const globalSettings = window?.wpseopilotV2Settings || {};
const aiEnabled = globalSettings.aiEnabled || false;
const aiProvider = globalSettings.aiProvider || 'none';
const aiPilot = globalSettings.aiPilot || null;

/**
 * Assistants page - Management view with create + stats.
 */
const Assistants = ({
  initialAssistant = null
}) => {
  const [view, setView] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('list'); // 'list', 'chat', 'create', 'edit'
  const [assistants, setAssistants] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [customAssistants, setCustomAssistants] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [stats, setStats] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [selectedAssistant, setSelectedAssistant] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [editingAssistant, setEditingAssistant] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);

  // Form state for create/edit
  const [form, setForm] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    name: '',
    description: '',
    system_prompt: '',
    initial_message: '',
    icon: '🤖',
    color: '#6366f1',
    model_id: '',
    is_active: true
  });
  const fetchData = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setLoading(true);
    try {
      const [assistantsRes, customRes, statsRes] = await Promise.all([_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/assistants'
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/assistants/custom'
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/assistants/stats'
      })]);
      if (assistantsRes.success) {
        setAssistants(assistantsRes.data);
      }
      if (customRes.success) {
        setCustomAssistants(customRes.data);
      }
      if (statsRes.success) {
        setStats(statsRes.data);
      }
    } catch (err) {
      console.error('Failed to fetch assistants:', err);
    } finally {
      setLoading(false);
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchData();
  }, [fetchData]);

  // Handle initial assistant from URL
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (initialAssistant && assistants.length > 0) {
      const found = assistants.find(a => a.id === initialAssistant);
      if (found) {
        setSelectedAssistant(found);
        setView('chat');
      }
    }
  }, [initialAssistant, assistants]);
  const handleSelectAssistant = assistant => {
    setSelectedAssistant(assistant);
    setView('chat');
  };
  const handleBack = () => {
    setSelectedAssistant(null);
    setEditingAssistant(null);
    setView('list');
    setForm({
      name: '',
      description: '',
      system_prompt: '',
      initial_message: '',
      icon: '🤖',
      color: '#6366f1',
      model_id: '',
      is_active: true
    });
  };
  const handleCreateNew = () => {
    setForm({
      name: '',
      description: '',
      system_prompt: '',
      initial_message: '',
      icon: '🤖',
      color: '#6366f1',
      model_id: '',
      is_active: true
    });
    setEditingAssistant(null);
    setView('create');
  };
  const handleEdit = assistant => {
    setForm({
      name: assistant.name || '',
      description: assistant.description || '',
      system_prompt: assistant.system_prompt || '',
      initial_message: assistant.initial_message || '',
      icon: assistant.icon || '🤖',
      color: assistant.color || '#6366f1',
      model_id: assistant.model_id || '',
      is_active: assistant.is_active !== false
    });
    setEditingAssistant(assistant);
    setView('edit');
  };
  const handleSave = async () => {
    if (!form.name || !form.system_prompt) {
      alert('Name and system prompt are required.');
      return;
    }
    setSaving(true);
    try {
      if (editingAssistant) {
        await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
          path: `/wpseopilot/v2/assistants/custom/${editingAssistant.id}`,
          method: 'PUT',
          data: form
        });
      } else {
        await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
          path: '/wpseopilot/v2/assistants/custom',
          method: 'POST',
          data: form
        });
      }
      await fetchData();
      handleBack();
    } catch (err) {
      console.error('Failed to save assistant:', err);
      alert('Failed to save assistant.');
    } finally {
      setSaving(false);
    }
  };
  const handleDelete = async id => {
    if (!window.confirm('Are you sure you want to delete this assistant?')) {
      return;
    }
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/assistants/custom/${id}`,
        method: 'DELETE'
      });
      await fetchData();
    } catch (err) {
      console.error('Failed to delete assistant:', err);
    }
  };
  const updateForm = (key, value) => {
    setForm(prev => ({
      ...prev,
      [key]: value
    }));
  };
  const icons = ['🤖', '💬', '📊', '🎯', '✨', '🔍', '📝', '💡', '🚀', '⚡'];
  const colors = ['#3b82f6', '#8b5cf6', '#6366f1', '#ec4899', '#f59e0b', '#10b981', '#ef4444', '#6b7280'];
  if (loading) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "loading-state"
    }, "Loading assistants..."));
  }

  // Chat view
  if (view === 'chat' && selectedAssistant) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page assistants-page"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page-header page-header--with-back"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "back-button",
      onClick: handleBack
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      strokeWidth: "2"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M15 18l-6-6 6-6"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "All Assistants")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page-header__info"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page-header__icon",
      style: {
        backgroundColor: `${selectedAssistant.color}15`,
        color: selectedAssistant.color
      }
    }, selectedAssistant.icon), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, selectedAssistant.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, selectedAssistant.description)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "assistants-chat-container"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_assistants__WEBPACK_IMPORTED_MODULE_3__.AssistantProvider, {
      key: selectedAssistant.id,
      assistantId: selectedAssistant.id,
      initialMessage: selectedAssistant.initial_message || selectedAssistant.initialMessage
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_assistants__WEBPACK_IMPORTED_MODULE_3__.AssistantChat, {
      suggestedPrompts: selectedAssistant.suggested_prompts || selectedAssistant.suggestedPrompts
    }))));
  }

  // Create/Edit form
  if (view === 'create' || view === 'edit') {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page-header page-header--with-back"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "back-button",
      onClick: handleBack
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      strokeWidth: "2"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M15 18l-6-6 6-6"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "All Assistants")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, view === 'edit' ? 'Edit Assistant' : 'Create Assistant'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Configure your custom AI assistant."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "assistants-form"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "panel"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Basic Info"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "form-row"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
      htmlFor: "name"
    }, "Name *"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      id: "name",
      type: "text",
      value: form.name,
      onChange: e => updateForm('name', e.target.value),
      placeholder: "My SEO Assistant"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "form-row"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
      htmlFor: "description"
    }, "Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      id: "description",
      type: "text",
      value: form.description,
      onChange: e => updateForm('description', e.target.value),
      placeholder: "A helpful assistant for..."
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "form-row"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Icon"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "icon-picker"
    }, icons.map(icon => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      key: icon,
      type: "button",
      className: `icon-option ${form.icon === icon ? 'active' : ''}`,
      onClick: () => updateForm('icon', icon)
    }, icon)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "form-row"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Color"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "color-picker"
    }, colors.map(color => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      key: color,
      type: "button",
      className: `color-option ${form.color === color ? 'active' : ''}`,
      style: {
        backgroundColor: color
      },
      onClick: () => updateForm('color', color)
    }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "panel"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "AI Configuration"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "form-row"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
      htmlFor: "system_prompt"
    }, "System Prompt *"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
      id: "system_prompt",
      value: form.system_prompt,
      onChange: e => updateForm('system_prompt', e.target.value),
      placeholder: "You are a helpful SEO assistant...",
      rows: 6
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "form-help"
    }, "Define the assistant's personality and expertise.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "form-row"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
      htmlFor: "initial_message"
    }, "Welcome Message"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
      id: "initial_message",
      value: form.initial_message,
      onChange: e => updateForm('initial_message', e.target.value),
      placeholder: "Hi! I'm here to help with...",
      rows: 2
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "form-row"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
      htmlFor: "model_id"
    }, "Model (optional)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      id: "model_id",
      type: "text",
      value: form.model_id,
      onChange: e => updateForm('model_id', e.target.value),
      placeholder: "Leave empty to use default model"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "form-help"
    }, "Use custom_ID for custom models (e.g., custom_1).")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "form-row form-row--checkbox"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      type: "checkbox",
      checked: form.is_active,
      onChange: e => updateForm('is_active', e.target.checked)
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Active")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "form-actions"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "button ghost",
      onClick: handleBack
    }, "Cancel"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "button primary",
      onClick: handleSave,
      disabled: saving
    }, saving ? 'Saving...' : view === 'edit' ? 'Save Changes' : 'Create Assistant'))));
  }

  // List view (default)
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "AI Assistants"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Manage your AI assistants and track usage.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header__actions"
  }, aiProvider === 'wp-ai-pilot' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: aiPilot?.settingsUrl || 'admin.php?page=wp-ai-pilot',
    className: "button ghost"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
  })), "WP AI Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleCreateNew
  }, "+ Create Assistant"))), !aiEnabled && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistants-notice"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistants-notice__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "32",
    height: "32",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistants-notice__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "AI Assistants Powered by WP AI Pilot"), aiPilot?.installed ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "WP AI Pilot is installed but needs configuration. Add an API key to enable AI assistants."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: aiPilot.settingsUrl || 'admin.php?page=wp-ai-pilot',
    className: "button primary"
  }, "Configure WP AI Pilot")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Install WP AI Pilot to access AI-powered assistants for SEO optimization, content generation, and more."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "plugin-install.php?s=wp+ai+pilot&tab=search",
    className: "button primary"
  }, "Install WP AI Pilot")))), stats && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stats-grid"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card__value"
  }, stats.total_messages), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card__label"
  }, "Total Messages")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card__value"
  }, stats.today), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card__label"
  }, "Today")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card__value"
  }, stats.this_week), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card__label"
  }, "This Week")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card__value"
  }, stats.this_month), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-card__label"
  }, "This Month"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistants-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, "Built-in Assistants"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistants-grid"
  }, assistants.filter(a => a.is_builtin).map(assistant => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: assistant.id,
    type: "button",
    className: "assistant-card",
    onClick: () => handleSelectAssistant(assistant)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-card__icon",
    style: {
      backgroundColor: `${assistant.color}15`,
      color: assistant.color
    }
  }, assistant.icon), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-card__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", {
    className: "assistant-card__name"
  }, assistant.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "assistant-card__desc"
  }, assistant.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-card__arrow",
    style: {
      color: assistant.color
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 18l6-6-6-6"
  }))))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistants-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, "Custom Assistants"), customAssistants.length === 0 ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "empty-state"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "No custom assistants yet."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button",
    onClick: handleCreateNew
  }, "Create your first assistant")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistants-grid"
  }, customAssistants.map(assistant => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: assistant.id,
    className: "assistant-card assistant-card--custom"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "assistant-card__main",
    onClick: () => handleSelectAssistant({
      ...assistant,
      id: `custom_${assistant.id}`
    })
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-card__icon",
    style: {
      backgroundColor: `${assistant.color || '#6366f1'}15`,
      color: assistant.color || '#6366f1'
    }
  }, assistant.icon || '🤖'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-card__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", {
    className: "assistant-card__name"
  }, assistant.name, !assistant.is_active && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "badge badge--muted"
  }, "Inactive")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "assistant-card__desc"
  }, assistant.description || 'Custom assistant'), assistant.usage > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "assistant-card__usage"
  }, assistant.usage, " messages"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "assistant-card__actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "icon-button",
    onClick: () => handleEdit(assistant),
    title: "Edit"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "icon-button icon-button--danger",
    onClick: () => handleDelete(assistant.id),
    title: "Delete"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
  })))))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Assistants);

/***/ },

/***/ "./src-v2/pages/Audit.js"
/*!*******************************!*\
  !*** ./src-v2/pages/Audit.js ***!
  \*******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);




// Issue type labels
const ISSUE_TYPE_LABELS = {
  title_missing: 'Missing Meta Title',
  title_length: 'Title Too Long',
  description_missing: 'Missing Meta Description',
  description_length: 'Description Too Long',
  missing_alt: 'Missing Alt Text',
  low_word_count: 'Low Word Count',
  missing_h1: 'Missing H1 Heading'
};

// Severity colors and labels
const SEVERITY_CONFIG = {
  high: {
    label: 'Critical',
    class: 'danger',
    color: 'var(--color-danger)'
  },
  medium: {
    label: 'Warning',
    class: 'warning',
    color: 'var(--color-warning)'
  },
  low: {
    label: 'Suggestion',
    class: 'muted',
    color: 'var(--color-muted)'
  }
};
const Audit = () => {
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [running, setRunning] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [auditData, setAuditData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [message, setMessage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    type: '',
    text: ''
  });
  const [expandedType, setExpandedType] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [applyingRecommendation, setApplyingRecommendation] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);

  // Fetch audit data
  const fetchAudit = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setLoading(true);
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/audit'
      });
      if (res.success) {
        setAuditData(res.data);
      }
    } catch (error) {
      console.error('Failed to fetch audit:', error);
      setMessage({
        type: 'error',
        text: 'Failed to load audit data.'
      });
    } finally {
      setLoading(false);
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchAudit();
  }, [fetchAudit]);

  // Run new audit
  const handleRunAudit = async () => {
    setRunning(true);
    setMessage({
      type: '',
      text: ''
    });
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/audit/run',
        method: 'POST',
        data: {
          post_type: 'any',
          limit: 100
        }
      });
      if (res.success) {
        setAuditData(res.data);
        setMessage({
          type: 'success',
          text: `Audit complete! Scanned ${res.data.scanned} posts.`
        });
      }
    } catch (error) {
      console.error('Failed to run audit:', error);
      setMessage({
        type: 'error',
        text: 'Failed to run audit.'
      });
    } finally {
      setRunning(false);
    }
  };

  // Apply recommendation
  const handleApplyRecommendation = async rec => {
    setApplyingRecommendation(rec.post_id);
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/audit/apply/${rec.post_id}`,
        method: 'POST',
        data: {
          title: rec.suggested_title,
          description: rec.suggested_description
        }
      });
      if (res.success) {
        setMessage({
          type: 'success',
          text: `Applied recommendations to "${rec.title}"`
        });
        // Remove from recommendations list
        setAuditData(prev => ({
          ...prev,
          recommendations: prev.recommendations.filter(r => r.post_id !== rec.post_id)
        }));
      }
    } catch (error) {
      console.error('Failed to apply recommendation:', error);
      setMessage({
        type: 'error',
        text: 'Failed to apply recommendation.'
      });
    } finally {
      setApplyingRecommendation(null);
    }
  };

  // Group issues by type
  const getIssuesByType = () => {
    if (!auditData?.issues) return {};
    const grouped = {};
    auditData.issues.forEach(issue => {
      if (!grouped[issue.type]) {
        grouped[issue.type] = [];
      }
      grouped[issue.type].push(issue);
    });
    return grouped;
  };

  // Get severity for an issue type (use highest severity in group)
  const getTypeSeverity = issues => {
    if (issues.some(i => i.severity === 'high')) return 'high';
    if (issues.some(i => i.severity === 'medium')) return 'medium';
    return 'low';
  };
  if (loading) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page-header"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "SEO Audit"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Scan your site for SEO issues and get actionable recommendations."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "loading-state"
    }, "Loading audit data..."));
  }
  const stats = auditData?.stats || {
    severity: {
      high: 0,
      medium: 0,
      low: 0
    },
    total: 0
  };
  const issuesByType = getIssuesByType();
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "SEO Audit"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Scan your site for SEO issues and get actionable recommendations.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "header-actions"
  }, auditData?.from_cache && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "cache-badge"
  }, "Cached results"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleRunAudit,
    disabled: running
  }, running ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "spinner"
  }), "Running Audit...") : 'Run Full Audit'))), message.text && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `notice-message ${message.type}`
  }, message.text), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stats-grid"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `audit-stat-card ${stats.severity.high > 0 ? 'has-issues' : 'no-issues'}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-icon danger"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 9v4M12 17h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-number"
  }, stats.severity.high), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-label"
  }, "Critical Issues"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-desc"
  }, stats.severity.high === 0 ? 'All critical checks passed' : 'Issues severely impacting SEO'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `audit-stat-card ${stats.severity.medium > 0 ? 'has-issues' : 'no-issues'}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-icon warning"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round",
    strokeLinejoin: "round"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-number"
  }, stats.severity.medium), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-label"
  }, "Warnings"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-desc"
  }, stats.severity.medium === 0 ? 'No warnings found' : 'Issues that may affect rankings'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-icon muted"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round",
    strokeLinejoin: "round"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-number"
  }, stats.severity.low), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-label"
  }, "Suggestions"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-desc"
  }, stats.severity.low === 0 ? 'No suggestions' : 'Optional improvements available'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-card info"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-icon info"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round",
    strokeLinejoin: "round"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-number"
  }, auditData?.scanned || 0), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-label"
  }, "Posts Scanned"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-stat-desc"
  }, stats.posts_with_issues || 0, " with issues")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "audit-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-section-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, "Issues by Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Click on an issue type to see affected posts.")), Object.keys(issuesByType).length === 0 ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "empty-state"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "48",
    height: "48",
    viewBox: "0 0 24 24",
    fill: "none",
    style: {
      color: 'var(--color-success)'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round",
    strokeLinejoin: "round"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "No issues found!"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Your site is in great SEO shape.")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-issues-list"
  }, Object.entries(issuesByType).map(([type, issues]) => {
    const severity = getTypeSeverity(issues);
    const config = SEVERITY_CONFIG[severity];
    const isExpanded = expandedType === type;
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      key: type,
      className: `audit-issue-group ${isExpanded ? 'expanded' : ''}`
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "audit-issue-header",
      onClick: () => setExpandedType(isExpanded ? null : type)
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "audit-issue-info"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: `severity-dot ${config.class}`
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "audit-issue-type"
    }, ISSUE_TYPE_LABELS[type] || type), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: `pill ${config.class}`
    }, issues.length)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      className: `chevron ${isExpanded ? 'expanded' : ''}`,
      width: "20",
      height: "20",
      viewBox: "0 0 24 24",
      fill: "none"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M19 9l-7 7-7-7",
      stroke: "currentColor",
      strokeWidth: "2",
      strokeLinecap: "round",
      strokeLinejoin: "round"
    }))), isExpanded && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "audit-issue-posts"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
      className: "data-table compact"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Post"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Issue"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Action"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, issues.map((issue, idx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
      key: idx
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: issue.edit_url,
      target: "_blank",
      rel: "noopener noreferrer"
    }, issue.title)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, issue.message), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: issue.edit_url,
      target: "_blank",
      rel: "noopener noreferrer",
      className: "link-button"
    }, "Edit Post"))))))));
  }))), auditData?.recommendations && auditData.recommendations.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "audit-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "audit-section-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, "Quick Fixes"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Apply suggested meta titles and descriptions with one click.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "recommendations-list"
  }, auditData.recommendations.map(rec => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: rec.post_id,
    className: "recommendation-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "recommendation-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, rec.title), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: rec.edit_url,
    target: "_blank",
    rel: "noopener noreferrer",
    className: "link-button small"
  }, "Edit Post")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "recommendation-suggestions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "suggestion-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Suggested Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "suggestion-value"
  }, rec.suggested_title)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "suggestion-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Suggested Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "suggestion-value"
  }, rec.suggested_description || '(No suggestion available)'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "recommendation-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary small",
    onClick: () => handleApplyRecommendation(rec),
    disabled: applyingRecommendation === rec.post_id
  }, applyingRecommendation === rec.post_id ? 'Applying...' : 'Apply Suggestions')))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Audit);

/***/ },

/***/ "./src-v2/pages/BulkEditor.js"
/*!************************************!*\
  !*** ./src-v2/pages/BulkEditor.js ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);



const BulkEditor = ({
  onNavigate
}) => {
  const [posts, setPosts] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [generating, setGenerating] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [postType, setPostType] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('post');
  const [page, setPage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(1);
  const [totalPages, setTotalPages] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(1);
  const [changes, setChanges] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const [filter, setFilter] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('all');
  const [searchQuery, setSearchQuery] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [selectedPosts, setSelectedPosts] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const perPage = 20;
  const fetchPosts = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setLoading(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/tools/bulk-editor/posts?post_type=${postType}&page=${page}&per_page=${perPage}&filter=${filter}&search=${encodeURIComponent(searchQuery)}`
      });
      if (response.success) {
        setPosts(response.data.posts);
        setTotalPages(response.data.total_pages);
      }
    } catch (error) {
      console.error('Failed to fetch posts:', error);
    } finally {
      setLoading(false);
    }
  }, [postType, page, filter, searchQuery]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchPosts();
  }, [fetchPosts]);
  const handleFieldChange = (postId, field, value) => {
    setChanges(prev => ({
      ...prev,
      [postId]: {
        ...prev[postId],
        [field]: value
      }
    }));
  };
  const getFieldValue = (post, field) => {
    if (changes[post.id] && changes[post.id][field] !== undefined) {
      return changes[post.id][field];
    }
    return post[field] || '';
  };
  const hasChanges = Object.keys(changes).length > 0;
  const handleSaveAll = async () => {
    if (!hasChanges) return;
    setSaving(true);
    try {
      const changesArray = Object.entries(changes).map(([postId, data]) => ({
        post_id: parseInt(postId),
        ...data
      }));
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/tools/bulk-editor/save',
        method: 'POST',
        data: {
          changes: changesArray
        }
      });
      if (response.success) {
        setChanges({});
        fetchPosts();
      }
    } catch (error) {
      console.error('Failed to save:', error);
    } finally {
      setSaving(false);
    }
  };
  const handleGenerateSuggestions = async (postIds = null) => {
    const idsToGenerate = postIds || selectedPosts;
    if (idsToGenerate.length === 0) return;
    setGenerating(idsToGenerate);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/tools/bulk-editor/generate',
        method: 'POST',
        data: {
          post_ids: idsToGenerate
        }
      });
      if (response.success && response.data.suggestions) {
        const newChanges = {
          ...changes
        };
        response.data.suggestions.forEach(suggestion => {
          newChanges[suggestion.post_id] = {
            ...newChanges[suggestion.post_id],
            seo_title: suggestion.title,
            seo_description: suggestion.description
          };
        });
        setChanges(newChanges);
      }
    } catch (error) {
      console.error('Failed to generate:', error);
    } finally {
      setGenerating(null);
    }
  };
  const handleSelectAll = () => {
    if (selectedPosts.length === posts.length) {
      setSelectedPosts([]);
    } else {
      setSelectedPosts(posts.map(p => p.id));
    }
  };
  const handleSelectPost = postId => {
    setSelectedPosts(prev => prev.includes(postId) ? prev.filter(id => id !== postId) : [...prev, postId]);
  };
  const getCharCountClass = (length, type) => {
    if (type === 'title') {
      if (length === 0) return 'empty';
      if (length < 30) return 'short';
      if (length > 60) return 'long';
      return 'good';
    } else {
      if (length === 0) return 'empty';
      if (length < 70) return 'short';
      if (length > 160) return 'long';
      return 'good';
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page bulk-editor-page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header__breadcrumb"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "breadcrumb-link",
    onClick: () => onNavigate('tools')
  }, "Tools"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breadcrumb-separator"
  }, "/"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Bulk Editor")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Smart Bulk Editor"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Edit SEO titles and descriptions in bulk with AI-powered suggestions.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header__actions"
  }, selectedPosts.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button--secondary",
    onClick: () => handleGenerateSuggestions(),
    disabled: generating
  }, generating ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "spinner"
  }), "Generating...") : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z"
  })), "Generate AI (", selectedPosts.length, ")")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button--primary",
    onClick: handleSaveAll,
    disabled: !hasChanges || saving
  }, saving ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "spinner"
  }), "Saving...") : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "17 21 17 13 7 13 7 21"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "7 3 7 8 15 8"
  })), "Save Changes ", hasChanges && `(${Object.keys(changes).length})`)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bulk-editor-controls"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bulk-editor-filters"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: postType,
    onChange: e => {
      setPostType(e.target.value);
      setPage(1);
    },
    className: "bulk-editor-select"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "post"
  }, "Posts"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "page"
  }, "Pages")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: filter,
    onChange: e => {
      setFilter(e.target.value);
      setPage(1);
    },
    className: "bulk-editor-select"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "all"
  }, "All"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "missing_title"
  }, "Missing SEO Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "missing_description"
  }, "Missing Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "missing_both"
  }, "Missing Both")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bulk-editor-search"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "11",
    cy: "11",
    r: "8"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 21l-4.35-4.35"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    placeholder: "Search posts...",
    value: searchQuery,
    onChange: e => {
      setSearchQuery(e.target.value);
      setPage(1);
    }
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bulk-editor-stats"
  }, selectedPosts.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "selected-count"
  }, selectedPosts.length, " selected"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bulk-editor-table-wrapper"
  }, loading ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bulk-editor-loading"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "spinner"
  }), "Loading posts...") : posts.length === 0 ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bulk-editor-empty"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "48",
    height: "48"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "14 2 14 8 20 8"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "No posts found matching your criteria.")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "bulk-editor-table"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", {
    className: "col-checkbox"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: selectedPosts.length === posts.length,
    onChange: handleSelectAll
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", {
    className: "col-title"
  }, "Post Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", {
    className: "col-seo-title"
  }, "SEO Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", {
    className: "col-seo-desc"
  }, "Meta Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", {
    className: "col-actions"
  }, "Actions"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, posts.map(post => {
    const seoTitle = getFieldValue(post, 'seo_title');
    const seoDesc = getFieldValue(post, 'seo_description');
    const isGenerating = generating && generating.includes(post.id);
    const hasPostChanges = !!changes[post.id];
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
      key: post.id,
      className: hasPostChanges ? 'row-modified' : ''
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
      className: "col-checkbox"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      type: "checkbox",
      checked: selectedPosts.includes(post.id),
      onChange: () => handleSelectPost(post.id)
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
      className: "col-title"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "post-info"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: post.edit_link,
      target: "_blank",
      rel: "noopener noreferrer",
      className: "post-title-link"
    }, post.title), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "post-type-badge"
    }, post.post_type))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
      className: "col-seo-title"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "field-wrapper"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      type: "text",
      value: seoTitle,
      onChange: e => handleFieldChange(post.id, 'seo_title', e.target.value),
      placeholder: post.title,
      className: isGenerating ? 'generating' : '',
      disabled: isGenerating
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: `char-count ${getCharCountClass(seoTitle.length, 'title')}`
    }, seoTitle.length, "/60"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
      className: "col-seo-desc"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "field-wrapper"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
      value: seoDesc,
      onChange: e => handleFieldChange(post.id, 'seo_description', e.target.value),
      placeholder: "Enter meta description...",
      rows: 2,
      className: isGenerating ? 'generating' : '',
      disabled: isGenerating
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: `char-count ${getCharCountClass(seoDesc.length, 'desc')}`
    }, seoDesc.length, "/160"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
      className: "col-actions"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "action-button",
      onClick: () => handleGenerateSuggestions([post.id]),
      disabled: isGenerating,
      title: "Generate AI suggestions"
    }, isGenerating ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "spinner-small"
    }) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      strokeWidth: "2",
      width: "16",
      height: "16"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z"
    })))));
  })))), totalPages > 1 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bulk-editor-pagination"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "pagination-button",
    onClick: () => setPage(p => Math.max(1, p - 1)),
    disabled: page === 1
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M15 18l-6-6 6-6"
  })), "Previous"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "pagination-info"
  }, "Page ", page, " of ", totalPages), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "pagination-button",
    onClick: () => setPage(p => Math.min(totalPages, p + 1)),
    disabled: page === totalPages
  }, "Next", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 18l6-6-6-6"
  })))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (BulkEditor);

/***/ },

/***/ "./src-v2/pages/ContentGaps.js"
/*!*************************************!*\
  !*** ./src-v2/pages/ContentGaps.js ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);



const ContentGaps = ({
  onNavigate
}) => {
  const [analyzing, setAnalyzing] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [results, setResults] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [generatingOutline, setGeneratingOutline] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [outline, setOutline] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [topic, setTopic] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [selectedCategory, setSelectedCategory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [categories, setCategories] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchCategories();
  }, []);
  const fetchCategories = async () => {
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wp/v2/categories?per_page=100'
      });
      setCategories(response);
    } catch (error) {
      console.error('Failed to fetch categories:', error);
    }
  };
  const handleAnalyze = async () => {
    setAnalyzing(true);
    setResults(null);
    setOutline(null);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/tools/content-gaps/analyze',
        method: 'POST',
        data: {
          topic: topic || null,
          category_id: selectedCategory || null
        }
      });
      if (response.success) {
        setResults(response.data);
      }
    } catch (error) {
      console.error('Failed to analyze:', error);
    } finally {
      setAnalyzing(false);
    }
  };
  const handleGenerateOutline = async gapTopic => {
    setGeneratingOutline(gapTopic);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/tools/content-gaps/outline',
        method: 'POST',
        data: {
          topic: gapTopic
        }
      });
      if (response.success) {
        setOutline({
          topic: gapTopic,
          ...response.data
        });
      }
    } catch (error) {
      console.error('Failed to generate outline:', error);
    } finally {
      setGeneratingOutline(null);
    }
  };
  const getPriorityColor = priority => {
    switch (priority) {
      case 'high':
        return '#d63638';
      case 'medium':
        return '#dba617';
      case 'low':
        return '#00a32a';
      default:
        return '#757575';
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page content-gaps-page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header__breadcrumb"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "breadcrumb-link",
    onClick: () => onNavigate('tools')
  }, "Tools"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breadcrumb-separator"
  }, "/"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Content Gaps Finder")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Content Gaps Finder"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Discover missing topics and content opportunities based on your existing content."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "content-gaps-input"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "input-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "input-group"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Focus Topic (optional)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    value: topic,
    onChange: e => setTopic(e.target.value),
    placeholder: "e.g., coffee brewing, SEO, cooking..."
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "input-group"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Category Filter"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: selectedCategory,
    onChange: e => setSelectedCategory(e.target.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: ""
  }, "All Categories"), categories.map(cat => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: cat.id,
    value: cat.id
  }, cat.name, " (", cat.count, ")")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button--primary analyze-button",
    onClick: handleAnalyze,
    disabled: analyzing
  }, analyzing ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "spinner"
  }), "Analyzing...") : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "11",
    cy: "11",
    r: "8"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 21l-4.35-4.35"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M11 8v6M8 11h6"
  })), "Find Content Gaps")))), analyzing && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "content-gaps-loading"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "loading-animation"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "spinner-large"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Analyzing your content..."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "loading-hint"
  }, "This may take a moment as we review your posts."))), results && !analyzing && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "content-gaps-results"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "results-summary"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "summary-stat"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-value"
  }, results.posts_analyzed), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-label"
  }, "Posts Analyzed")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "summary-stat"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-value"
  }, results.gaps?.length || 0), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-label"
  }, "Gaps Found")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "summary-stat"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-value"
  }, results.clusters?.length || 0), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-label"
  }, "Content Clusters"))), results.existing_topics?.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "results-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Your Current Topics"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "topic-tags"
  }, results.existing_topics.map((topic, idx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    key: idx,
    className: "topic-tag"
  }, topic)))), results.gaps?.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "results-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Content Gaps"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "section-desc"
  }, "Topics you should consider writing about:"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "gaps-grid"
  }, results.gaps.map((gap, idx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: idx,
    className: "gap-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "gap-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", {
    className: "gap-title"
  }, gap.topic), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "gap-priority",
    style: {
      backgroundColor: getPriorityColor(gap.priority)
    }
  }, gap.priority)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "gap-reason"
  }, gap.reason), gap.search_volume && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "gap-meta"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "gap-volume"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "14",
    height: "14"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M3 3v18h18"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M18 9l-5 5-4-4-3 3"
  })), "~", gap.search_volume, "/mo")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button--small",
    onClick: () => handleGenerateOutline(gap.topic),
    disabled: generatingOutline === gap.topic
  }, generatingOutline === gap.topic ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "spinner-small"
  }), "Generating...") : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "14",
    height: "14"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "14 2 14 8 20 8"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "16",
    y1: "13",
    x2: "8",
    y2: "13"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "16",
    y1: "17",
    x2: "8",
    y2: "17"
  })), "Generate Outline")))))), results.clusters?.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "results-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Content Clusters"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "section-desc"
  }, "Group related content for better internal linking:"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "clusters-list"
  }, results.clusters.map((cluster, idx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: idx,
    className: "cluster-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "cluster-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, cluster.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "cluster-count"
  }, cluster.posts?.length || 0, " posts")), cluster.missing_subtopics?.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "cluster-missing"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "missing-label"
  }, "Missing subtopics:"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "missing-tags"
  }, cluster.missing_subtopics.map((sub, subIdx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    key: subIdx,
    className: "missing-tag"
  }, sub))))))))), outline && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "outline-modal-backdrop",
    onClick: () => setOutline(null)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "outline-modal",
    onClick: e => e.stopPropagation()
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "outline-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Content Outline: ", outline.topic), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "outline-close",
    onClick: () => setOutline(null)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "20",
    height: "20"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "18",
    y1: "6",
    x2: "6",
    y2: "18"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "6",
    y1: "6",
    x2: "18",
    y2: "18"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "outline-content"
  }, outline.suggested_title && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "outline-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Suggested Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "suggested-title"
  }, outline.suggested_title)), outline.meta_description && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "outline-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Meta Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, outline.meta_description)), outline.outline?.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "outline-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Content Structure"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
    className: "outline-list"
  }, outline.outline.map((item, idx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    key: idx,
    className: `outline-item outline-item--${item.level || 'h2'}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "outline-heading"
  }, item.heading), item.points?.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
    className: "outline-points"
  }, item.points.map((point, pIdx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    key: pIdx
  }, point))))))), outline.target_keywords?.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "outline-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Target Keywords"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "keyword-tags"
  }, outline.target_keywords.map((kw, idx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    key: idx,
    className: "keyword-tag"
  }, kw))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "outline-footer"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button--secondary",
    onClick: () => {
      const text = `# ${outline.suggested_title || outline.topic}\n\n${outline.outline?.map(item => `## ${item.heading}\n${item.points?.map(p => `- ${p}`).join('\n') || ''}`).join('\n\n') || ''}`;
      navigator.clipboard.writeText(text);
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "14",
    height: "14"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "9",
    y: "9",
    width: "13",
    height: "13",
    rx: "2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"
  })), "Copy Outline"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button--primary",
    onClick: () => setOutline(null)
  }, "Done")))), !results && !analyzing && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "content-gaps-empty"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "1.5",
    width: "64",
    height: "64"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "9",
    y: "3",
    width: "6",
    height: "4",
    rx: "1"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 12v4M10 14h4"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Find Your Content Opportunities"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Enter a focus topic or select a category, then click \"Find Content Gaps\" to discover what topics you should be writing about based on your existing content.")));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ContentGaps);

/***/ },

/***/ "./src-v2/pages/Dashboard.js"
/*!***********************************!*\
  !*** ./src-v2/pages/Dashboard.js ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);




// Score level configuration
const SCORE_LEVELS = {
  excellent: {
    label: 'Excellent',
    color: 'var(--color-success)',
    class: 'success'
  },
  good: {
    label: 'Good',
    color: 'var(--color-success)',
    class: 'success'
  },
  fair: {
    label: 'Fair',
    color: 'var(--color-warning)',
    class: 'warning'
  },
  poor: {
    label: 'Needs Work',
    color: 'var(--color-danger)',
    class: 'danger'
  }
};

// Priority order for notification types
const PRIORITY_ORDER = {
  error: 1,
  warning: 2,
  info: 3
};

// Map notification actions to internal views
const ACTION_VIEW_MAP = {
  redirects: 'redirects',
  '404': '404-log',
  audit: 'audit',
  sitemap: 'sitemap',
  content: 'audit',
  seo: 'audit'
};
const Dashboard = ({
  onNavigate
}) => {
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [data, setData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [dismissing, setDismissing] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [showAllNotifications, setShowAllNotifications] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);

  // Fetch dashboard data
  const fetchDashboard = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setLoading(true);
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/dashboard'
      });
      if (res.success) {
        setData(res.data);
      }
    } catch (error) {
      console.error('Failed to fetch dashboard:', error);
    } finally {
      setLoading(false);
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchDashboard();
  }, [fetchDashboard]);

  // Dismiss notification
  const handleDismissNotification = async id => {
    setDismissing(id);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/dashboard/notifications/${id}/dismiss`,
        method: 'POST'
      });
      setData(prev => ({
        ...prev,
        notifications: prev.notifications.filter(n => n.id !== id)
      }));
    } catch (error) {
      console.error('Failed to dismiss notification:', error);
    } finally {
      setDismissing(null);
    }
  };

  // Handle navigation
  const handleNavigation = view => {
    if (onNavigate) {
      onNavigate(view);
    }
  };

  // Navigate to audit
  const handleRunAudit = () => {
    handleNavigation('audit');
  };
  if (loading) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page-header"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Dashboard"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "SEO health, content insights, and optimization status at a glance."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "loading-state"
    }, "Loading dashboard data..."));
  }
  const seoScore = data?.seo_score || {
    score: 0,
    level: 'poor',
    issues: 0
  };
  const coverage = data?.content_coverage || {
    total: 0,
    optimized: 0,
    pending: 0,
    daily_stats: []
  };
  const sitemap = data?.sitemap || {
    enabled: false,
    total_urls: 0,
    last_generated: 'Never'
  };
  const redirects = data?.redirects || {
    active: 0,
    hits_today: 0,
    suggestions: 0
  };
  const errors404 = data?.errors_404 || {
    total: 0,
    last_30_days: 0
  };
  const schema = data?.schema || {
    status: 'partial',
    types: []
  };
  const allNotifications = data?.notifications || [];

  // Sort notifications by priority
  const sortedNotifications = [...allNotifications].sort((a, b) => {
    const priorityA = PRIORITY_ORDER[a.type] || 99;
    const priorityB = PRIORITY_ORDER[b.type] || 99;
    return priorityA - priorityB;
  });

  // Show max 3 notifications on dashboard, or all if expanded
  const visibleNotifications = showAllNotifications ? sortedNotifications : sortedNotifications.slice(0, 3);
  const hasMoreNotifications = sortedNotifications.length > 3;
  const scoreConfig = SCORE_LEVELS[seoScore.level] || SCORE_LEVELS.poor;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Dashboard"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "SEO health, content insights, and optimization status at a glance.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleRunAudit
  }, "Run SEO Audit")), visibleNotifications.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "dashboard-notifications"
  }, visibleNotifications.map(notif => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: notif.id,
    className: `notification-banner notification-banner--${notif.type}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "notification-icon"
  }, notif.type === 'error' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "10",
    stroke: "currentColor",
    strokeWidth: "2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 8v4M12 16h.01",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round"
  })), notif.type === 'warning' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 9v4m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round",
    strokeLinejoin: "round"
  })), notif.type === 'info' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "10",
    stroke: "currentColor",
    strokeWidth: "2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 16v-4m0-4h.01",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "notification-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, notif.title), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, notif.message)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "notification-actions"
  }, notif.action && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button small",
    onClick: () => handleNavigation(ACTION_VIEW_MAP[notif.category] || 'tools')
  }, notif.action.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost small",
    onClick: () => handleDismissNotification(notif.id),
    disabled: dismissing === notif.id,
    "aria-label": "Dismiss notification"
  }, dismissing === notif.id ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "loading-dots"
  }, "...") : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "16",
    height: "16",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M18 6L6 18M6 6l12 12",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round"
  })))))), hasMoreNotifications && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "notifications-toggle",
    onClick: () => setShowAllNotifications(!showAllNotifications)
  }, showAllNotifications ? 'Show less' : `View all ${sortedNotifications.length} notifications`)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "dashboard-grid"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "dashboard-card seo-score-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "card-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "SEO Score"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `pill ${scoreConfig.class}`
  }, scoreConfig.label)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "seo-score-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "score-gauge"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 120 120",
    className: "gauge-svg"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    className: "gauge-bg",
    cx: "60",
    cy: "60",
    r: "50",
    fill: "none",
    strokeWidth: "10"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    className: "gauge-fill",
    cx: "60",
    cy: "60",
    r: "50",
    fill: "none",
    strokeWidth: "10",
    strokeDasharray: `${seoScore.score / 100 * 314} 314`,
    strokeLinecap: "round",
    style: {
      stroke: scoreConfig.color
    }
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "gauge-center"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "gauge-value"
  }, seoScore.score, "%"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "gauge-label"
  }, seoScore.posts_scored || 0, " posts"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "score-breakdown"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "breakdown-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-dot excellent"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-label"
  }, "Excellent (80+)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-value"
  }, seoScore.distribution?.excellent || 0)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "breakdown-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-dot good"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-label"
  }, "Good (60-79)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-value"
  }, seoScore.distribution?.good || 0)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "breakdown-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-dot fair"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-label"
  }, "Fair (40-59)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-value"
  }, seoScore.distribution?.fair || 0)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "breakdown-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-dot poor"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-label"
  }, "Poor (<40)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breakdown-value"
  }, seoScore.distribution?.poor || 0)))), seoScore.issues > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "card-note"
  }, seoScore.issues, " post", seoScore.issues !== 1 ? 's' : '', " could use optimization.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "dashboard-card content-coverage-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "card-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Content Coverage"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "pill"
  }, coverage.coverage_pct || 0, "% Optimized")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "coverage-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "coverage-chart"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "spark-bars",
    "aria-hidden": "true"
  }, (coverage.daily_stats || []).map((day, idx) => {
    const maxOptimized = Math.max(...coverage.daily_stats.map(d => d.optimized || 0), 1);
    const height = Math.max(15, (day.optimized || 0) / maxOptimized * 100);
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      key: idx,
      className: "spark-bar-wrapper",
      title: `${day.label}: ${day.optimized} optimized`
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      style: {
        height: `${height}%`
      }
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "spark-label"
    }, day.label));
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "coverage-stats"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "coverage-stat"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "coverage-stat-value"
  }, coverage.optimized || 0), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "coverage-stat-label"
  }, "Optimized")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "coverage-stat pending"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "coverage-stat-value"
  }, coverage.pending || 0), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "coverage-stat-label"
  }, "Pending")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "card-note"
  }, coverage.total || 0, " total pages \xB7 ", coverage.with_title || 0, " with titles \xB7 ", coverage.with_description || 0, " with descriptions"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "card-grid",
    style: {
      marginTop: 'var(--space-lg)'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "card-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Sitemap Status"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `pill ${sitemap.enabled ? 'success' : 'warning'}`
  }, sitemap.status_label || (sitemap.enabled ? 'Active' : 'Disabled'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `status-dot ${sitemap.enabled ? 'success' : 'warning'}`,
    "aria-hidden": "true"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-title"
  }, sitemap.enabled ? `${sitemap.total_urls} URLs indexed` : 'Sitemap disabled'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-subtitle"
  }, sitemap.enabled ? `Last generated: ${sitemap.last_generated}` : 'Enable to help search engines'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost small",
    onClick: () => handleNavigation('sitemap')
  }, sitemap.enabled ? 'View Sitemap' : 'Enable Sitemap')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "card-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Redirects"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `pill ${redirects.active > 0 ? 'success' : ''}`
  }, redirects.active, " Active")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `status-dot ${redirects.suggestions > 0 ? 'warning' : 'success'}`,
    "aria-hidden": "true"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-title"
  }, redirects.suggestions > 0 ? `${redirects.suggestions} pending suggestion${redirects.suggestions !== 1 ? 's' : ''}` : 'All redirects working'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-subtitle"
  }, redirects.hits_today || 0, " hits today"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost small",
    onClick: () => handleNavigation('redirects')
  }, "Manage Redirects")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "card-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "404 Errors"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `pill ${errors404.last_30_days > 0 ? 'warning' : 'success'}`
  }, errors404.last_30_days > 0 ? `${errors404.last_30_days} Found` : 'None')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `status-dot ${errors404.last_30_days > 0 ? 'warning' : 'success'}`,
    "aria-hidden": "true"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-title"
  }, errors404.last_7_days > 0 ? `${errors404.last_7_days} errors this week` : 'No recent errors'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-subtitle"
  }, "Last 30 days"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost small",
    onClick: () => handleNavigation('404-log')
  }, "View 404 Log")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "card-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Schema Markup"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `pill ${schema.status === 'valid' ? 'success' : ''}`
  }, schema.status_label || schema.status)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `status-dot ${schema.status === 'valid' ? 'success' : ''}`,
    "aria-hidden": "true"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-title"
  }, schema.types?.length > 0 ? schema.types.slice(0, 3).join(', ') : 'Basic markup'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "status-subtitle"
  }, schema.types?.length > 3 ? `+${schema.types.length - 3} more types` : 'Schema types active'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost small",
    onClick: () => handleNavigation('search-appearance')
  }, "Configure Schema"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "dashboard-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Quick Actions"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "actions-grid"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "action-card",
    onClick: () => handleNavigation('audit')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "action-icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round",
    strokeLinejoin: "round"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "action-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Run SEO Audit"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Scan for issues"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "action-card",
    onClick: () => handleNavigation('redirects')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "action-icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M13 10V3L4 14h7v7l9-11h-7z",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round",
    strokeLinejoin: "round"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "action-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Manage Redirects"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, redirects.suggestions > 0 ? `${redirects.suggestions} suggestions` : 'All good'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "action-card",
    onClick: () => handleNavigation('sitemap')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "action-icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M4 6h16M4 12h16M4 18h10",
    stroke: "currentColor",
    strokeWidth: "2",
    strokeLinecap: "round"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "action-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "View Sitemap"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, sitemap.total_urls, " URLs"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "action-card",
    onClick: () => handleNavigation('ai-assistant')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "action-icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z",
    stroke: "currentColor",
    strokeWidth: "2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "9",
    cy: "13",
    r: "1",
    fill: "currentColor"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "15",
    cy: "13",
    r: "1",
    fill: "currentColor"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "action-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "AI Assistant"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Generate content"))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Dashboard);

/***/ },

/***/ "./src-v2/pages/InternalLinking.js"
/*!*****************************************!*\
  !*** ./src-v2/pages/InternalLinking.js ***!
  \*****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _components_SubTabs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/SubTabs */ "./src-v2/components/SubTabs.js");
/* harmony import */ var _hooks_useUrlTab__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../hooks/useUrlTab */ "./src-v2/hooks/useUrlTab.js");





const linkingTabs = [{
  id: 'rules',
  label: 'Rules'
}, {
  id: 'categories',
  label: 'Categories'
}, {
  id: 'utm-templates',
  label: 'UTM Templates'
}, {
  id: 'settings',
  label: 'Settings'
}];
const HEADING_LEVELS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
const InternalLinking = () => {
  const [activeTab, setActiveTab] = (0,_hooks_useUrlTab__WEBPACK_IMPORTED_MODULE_4__["default"])({
    tabs: linkingTabs,
    defaultTab: 'rules'
  });

  // Rules state
  const [rules, setRules] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [rulesLoading, setRulesLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [categories, setCategories] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [templates, setTemplates] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [settings, setSettings] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const [stats, setStats] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    total_rules: 0,
    active_rules: 0,
    categories: 0,
    utm_templates: 0
  });

  // Filters
  const [filterStatus, setFilterStatus] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [filterCategory, setFilterCategory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [filterSearch, setFilterSearch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');

  // Bulk selection
  const [selectedRules, setSelectedRules] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [bulkAction, setBulkAction] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [bulkCategory, setBulkCategory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');

  // Modal states
  const [ruleModalOpen, setRuleModalOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [editingRule, setEditingRule] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [categoryModalOpen, setCategoryModalOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [editingCategory, setEditingCategory] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [templateModalOpen, setTemplateModalOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [editingTemplate, setEditingTemplate] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);

  // Fetch all data
  const fetchData = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setRulesLoading(true);
    try {
      const [rulesRes, categoriesRes, templatesRes, settingsRes, statsRes] = await Promise.all([_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/internal-links/rules'
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/internal-links/categories'
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/internal-links/templates'
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/internal-links/settings'
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/internal-links/stats'
      })]);
      if (rulesRes.success) setRules(rulesRes.data);
      if (categoriesRes.success) setCategories(categoriesRes.data);
      if (templatesRes.success) setTemplates(templatesRes.data);
      if (settingsRes.success) setSettings(settingsRes.data);
      if (statsRes.success) setStats(statsRes.data);
    } catch (error) {
      console.error('Failed to fetch internal linking data:', error);
    } finally {
      setRulesLoading(false);
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchData();
  }, [fetchData]);

  // Filter rules
  const filteredRules = rules.filter(rule => {
    if (filterStatus && rule.status !== filterStatus) return false;
    if (filterCategory && rule.category !== filterCategory) return false;
    if (filterSearch) {
      const search = filterSearch.toLowerCase();
      const inTitle = (rule.title || '').toLowerCase().includes(search);
      const inKeywords = (rule.keywords || []).some(k => k.toLowerCase().includes(search));
      if (!inTitle && !inKeywords) return false;
    }
    return true;
  });

  // Get category by ID
  const getCategoryById = id => categories.find(c => c.id === id);
  const getTemplateById = id => templates.find(t => t.id === id);

  // Rule actions
  const handleDeleteRule = async id => {
    if (!window.confirm('Are you sure you want to delete this rule?')) return;
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/internal-links/rules/${id}`,
        method: 'DELETE'
      });
      setRules(rules.filter(r => r.id !== id));
      setSelectedRules(selectedRules.filter(rid => rid !== id));
    } catch (error) {
      console.error('Failed to delete rule:', error);
    }
  };
  const handleToggleRule = async id => {
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/internal-links/rules/${id}/toggle`,
        method: 'POST'
      });
      if (res.success) {
        setRules(rules.map(r => r.id === id ? res.data : r));
      }
    } catch (error) {
      console.error('Failed to toggle rule:', error);
    }
  };
  const handleDuplicateRule = async id => {
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/internal-links/rules/${id}/duplicate`,
        method: 'POST'
      });
      if (res.success) {
        setRules([res.data, ...rules]);
      }
    } catch (error) {
      console.error('Failed to duplicate rule:', error);
    }
  };
  const handleBulkAction = async () => {
    if (!bulkAction || selectedRules.length === 0) return;
    try {
      const payload = {
        ids: selectedRules,
        action: bulkAction
      };
      if (bulkAction === 'change_category') {
        payload.category = bulkCategory;
      }
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/internal-links/rules/bulk',
        method: 'POST',
        data: payload
      });
      setSelectedRules([]);
      setBulkAction('');
      setBulkCategory('');
      fetchData();
    } catch (error) {
      console.error('Failed to perform bulk action:', error);
    }
  };
  const handleSelectAll = e => {
    if (e.target.checked) {
      setSelectedRules(filteredRules.map(r => r.id));
    } else {
      setSelectedRules([]);
    }
  };
  const handleSelectRule = id => {
    if (selectedRules.includes(id)) {
      setSelectedRules(selectedRules.filter(rid => rid !== id));
    } else {
      setSelectedRules([...selectedRules, id]);
    }
  };

  // Category actions
  const handleDeleteCategory = async id => {
    const category = getCategoryById(id);
    if (!category) return;
    const rulesInCategory = rules.filter(r => r.category === id).length;
    let reassign = null;
    if (rulesInCategory > 0) {
      const message = `This category has ${rulesInCategory} rule(s). Delete anyway and remove category from rules?`;
      if (!window.confirm(message)) return;
      reassign = '__none__';
    } else {
      if (!window.confirm('Are you sure you want to delete this category?')) return;
    }
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/internal-links/categories/${id}`,
        method: 'DELETE',
        data: reassign ? {
          reassign
        } : undefined
      });
      setCategories(categories.filter(c => c.id !== id));
      if (reassign) {
        setRules(rules.map(r => r.category === id ? {
          ...r,
          category: ''
        } : r));
      }
    } catch (error) {
      console.error('Failed to delete category:', error);
    }
  };

  // Template actions
  const handleDeleteTemplate = async id => {
    if (!window.confirm('Are you sure you want to delete this UTM template?')) return;
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/internal-links/templates/${id}`,
        method: 'DELETE'
      });
      setTemplates(templates.filter(t => t.id !== id));
    } catch (error) {
      console.error('Failed to delete template:', error);
    }
  };

  // Save settings
  const handleSaveSettings = async () => {
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/internal-links/settings',
        method: 'POST',
        data: settings
      });
      if (res.success) {
        setSettings(res.data);
      }
    } catch (error) {
      console.error('Failed to save settings:', error);
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Internal Linking"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Automatically add internal links to your content based on keywords.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header__stats"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-chip"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-chip__value"
  }, stats.active_rules), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-chip__label"
  }, "Active Rules")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-chip"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-chip__value"
  }, stats.categories), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-chip__label"
  }, "Categories")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_SubTabs__WEBPACK_IMPORTED_MODULE_3__["default"], {
    tabs: linkingTabs,
    activeTab: activeTab,
    onChange: setActiveTab,
    ariaLabel: "Internal linking sections"
  }), activeTab === 'rules' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(RulesTab, {
    rules: filteredRules,
    rulesLoading: rulesLoading,
    categories: categories,
    templates: templates,
    selectedRules: selectedRules,
    filterStatus: filterStatus,
    filterCategory: filterCategory,
    filterSearch: filterSearch,
    bulkAction: bulkAction,
    bulkCategory: bulkCategory,
    onFilterStatus: setFilterStatus,
    onFilterCategory: setFilterCategory,
    onFilterSearch: setFilterSearch,
    onSelectAll: handleSelectAll,
    onSelectRule: handleSelectRule,
    onEditRule: rule => {
      setEditingRule(rule);
      setRuleModalOpen(true);
    },
    onDeleteRule: handleDeleteRule,
    onToggleRule: handleToggleRule,
    onDuplicateRule: handleDuplicateRule,
    onBulkAction: setBulkAction,
    onBulkCategory: setBulkCategory,
    onApplyBulk: handleBulkAction,
    onAddRule: () => {
      setEditingRule(null);
      setRuleModalOpen(true);
    },
    getCategoryById: getCategoryById,
    getTemplateById: getTemplateById
  }), activeTab === 'categories' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(CategoriesTab, {
    categories: categories,
    templates: templates,
    onEdit: cat => {
      setEditingCategory(cat);
      setCategoryModalOpen(true);
    },
    onDelete: handleDeleteCategory,
    onAdd: () => {
      setEditingCategory(null);
      setCategoryModalOpen(true);
    }
  }), activeTab === 'utm-templates' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(TemplatesTab, {
    templates: templates,
    onEdit: tpl => {
      setEditingTemplate(tpl);
      setTemplateModalOpen(true);
    },
    onDelete: handleDeleteTemplate,
    onAdd: () => {
      setEditingTemplate(null);
      setTemplateModalOpen(true);
    }
  }), activeTab === 'settings' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(SettingsTab, {
    settings: settings,
    onChange: setSettings,
    onSave: handleSaveSettings
  }), ruleModalOpen && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(RuleModal, {
    rule: editingRule,
    categories: categories,
    templates: templates,
    onClose: () => {
      setRuleModalOpen(false);
      setEditingRule(null);
    },
    onSave: saved => {
      if (editingRule) {
        setRules(rules.map(r => r.id === saved.id ? saved : r));
      } else {
        setRules([saved, ...rules]);
      }
      setRuleModalOpen(false);
      setEditingRule(null);
    }
  }), categoryModalOpen && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(CategoryModal, {
    category: editingCategory,
    templates: templates,
    onClose: () => {
      setCategoryModalOpen(false);
      setEditingCategory(null);
    },
    onSave: saved => {
      if (editingCategory) {
        setCategories(categories.map(c => c.id === saved.id ? saved : c));
      } else {
        setCategories([...categories, saved]);
      }
      setCategoryModalOpen(false);
      setEditingCategory(null);
    }
  }), templateModalOpen && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(TemplateModal, {
    template: editingTemplate,
    onClose: () => {
      setTemplateModalOpen(false);
      setEditingTemplate(null);
    },
    onSave: saved => {
      if (editingTemplate) {
        setTemplates(templates.map(t => t.id === saved.id ? saved : t));
      } else {
        setTemplates([...templates, saved]);
      }
      setTemplateModalOpen(false);
      setEditingTemplate(null);
    }
  }));
};

// Rules Tab Component
const RulesTab = ({
  rules,
  rulesLoading,
  categories,
  templates,
  selectedRules,
  filterStatus,
  filterCategory,
  filterSearch,
  bulkAction,
  bulkCategory,
  onFilterStatus,
  onFilterCategory,
  onFilterSearch,
  onSelectAll,
  onSelectRule,
  onEditRule,
  onDeleteRule,
  onToggleRule,
  onDuplicateRule,
  onBulkAction,
  onBulkCategory,
  onApplyBulk,
  onAddRule,
  getCategoryById,
  getTemplateById
}) => {
  const clearFilters = () => {
    onFilterStatus('');
    onFilterCategory('');
    onFilterSearch('');
  };
  const hasFilters = filterStatus || filterCategory || filterSearch;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Linking Rules"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Define keywords and their target URLs for automatic linking.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: onAddRule
  }, "Add Rule")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "filters-bar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "filters-bar__filters"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: filterStatus,
    onChange: e => onFilterStatus(e.target.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: ""
  }, "All statuses"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "active"
  }, "Active"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "inactive"
  }, "Inactive")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: filterCategory,
    onChange: e => onFilterCategory(e.target.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: ""
  }, "All categories"), categories.map(cat => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: cat.id,
    value: cat.id
  }, cat.name))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "search",
    placeholder: "Search rules...",
    value: filterSearch,
    onChange: e => onFilterSearch(e.target.value)
  }), hasFilters && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "link-button",
    onClick: clearFilters
  }, "Clear"))), rulesLoading ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "loading-state"
  }, "Loading rules...") : rules.length === 0 ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "empty-state"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "empty-state__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "1.5",
    width: "48",
    height: "48"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, hasFilters ? 'No rules match your filters' : 'No linking rules yet'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, hasFilters ? 'Try adjusting your filters.' : 'Create your first internal link rule to get started.'), !hasFilters && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: onAddRule
  }, "Create Rule")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "data-table"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", {
    className: "check-column"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: selectedRules.length === rules.length && rules.length > 0,
    onChange: onSelectAll
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Category"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Keywords"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Destination"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Status"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Actions"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, rules.map(rule => {
    const category = getCategoryById(rule.category);
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
      key: rule.id
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
      className: "check-column"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      type: "checkbox",
      checked: selectedRules.includes(rule.id),
      onChange: () => onSelectRule(rule.id)
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "link-button",
      onClick: () => onEditRule(rule)
    }, rule.title))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, category ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "category-pill",
      style: {
        '--pill-color': category.color
      }
    }, category.name) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted"
    }, "\u2014")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "keywords-preview"
    }, (rule.keywords || []).slice(0, 3).join(', '), (rule.keywords || []).length > 3 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted"
    }, " +", rule.keywords.length - 3))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, rule.destination_url ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: rule.destination_url,
      target: "_blank",
      rel: "noopener noreferrer",
      className: "destination-link"
    }, rule.destination_label) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted"
    }, "\u2014")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: `pill ${rule.status === 'active' ? 'success' : 'muted'}`
    }, rule.status === 'active' ? 'Active' : 'Inactive')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "action-buttons"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "link-button",
      onClick: () => onEditRule(rule)
    }, "Edit"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "link-button",
      onClick: () => onDuplicateRule(rule.id)
    }, "Duplicate"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "link-button",
      onClick: () => onToggleRule(rule.id)
    }, rule.status === 'active' ? 'Deactivate' : 'Activate'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "link-button danger",
      onClick: () => onDeleteRule(rule.id)
    }, "Delete"))));
  }))), selectedRules.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bulk-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "bulk-actions__count"
  }, selectedRules.length, " selected"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: bulkAction,
    onChange: e => onBulkAction(e.target.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: ""
  }, "Bulk actions"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "activate"
  }, "Activate"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "deactivate"
  }, "Deactivate"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "change_category"
  }, "Change category"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "delete"
  }, "Delete")), bulkAction === 'change_category' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: bulkCategory,
    onChange: e => onBulkCategory(e.target.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "__none__"
  }, "Remove category"), categories.map(cat => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: cat.id,
    value: cat.id
  }, cat.name))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button",
    onClick: onApplyBulk,
    disabled: !bulkAction
  }, "Apply"))));
};

// Categories Tab Component
const CategoriesTab = ({
  categories,
  templates,
  onEdit,
  onDelete,
  onAdd
}) => {
  const getTemplateById = id => templates.find(t => t.id === id);
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Rule Categories"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Group rules, pick a color, and set per-category limits.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: onAdd
  }, "Add Category")), categories.length === 0 ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "empty-state"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "No categories yet"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Create categories to organize your linking rules."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: onAdd
  }, "Create Category")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "data-table"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Name"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Color"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Default UTM"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Cap"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Rules"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Actions"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, categories.map(cat => {
    const template = getTemplateById(cat.default_utm);
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
      key: cat.id
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, cat.name), cat.description && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "muted small"
    }, cat.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "color-chip",
      style: {
        backgroundColor: cat.color
      }
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, template ? template.name : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted"
    }, "\u2014")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, cat.category_cap || (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted"
    }, "\u2014")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, cat.rule_count || 0), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "action-buttons"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "link-button",
      onClick: () => onEdit(cat)
    }, "Edit"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "link-button danger",
      onClick: () => onDelete(cat.id)
    }, "Delete"))));
  }))));
};

// Templates Tab Component
const TemplatesTab = ({
  templates,
  onEdit,
  onDelete,
  onAdd
}) => {
  const applyToLabel = value => {
    switch (value) {
      case 'internal':
        return 'Internal only';
      case 'external':
        return 'External only';
      default:
        return 'Both';
    }
  };
  const appendModeLabel = value => {
    switch (value) {
      case 'always_overwrite':
        return 'Always overwrite';
      case 'never':
        return 'Never overwrite';
      default:
        return 'Append if missing';
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "UTM Templates"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Define reusable parameter sets for consistent tracking.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: onAdd
  }, "Add Template")), templates.length === 0 ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "empty-state"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "No UTM templates yet"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Create templates to add tracking parameters to your links."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: onAdd
  }, "Create Template")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "data-table"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Name"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "utm_source"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "utm_medium"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "utm_campaign"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Apply to"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Mode"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Actions"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, templates.map(tpl => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
    key: tpl.id
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, tpl.name)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, tpl.utm_source || (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "muted"
  }, "\u2014")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, tpl.utm_medium || (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "muted"
  }, "\u2014")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, tpl.utm_campaign || (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "muted"
  }, "\u2014")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, applyToLabel(tpl.apply_to)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, appendModeLabel(tpl.append_mode)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "action-buttons"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "link-button",
    onClick: () => onEdit(tpl)
  }, "Edit"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "link-button danger",
    onClick: () => onDelete(tpl.id)
  }, "Delete"))))))));
};

// Settings Tab Component
const SettingsTab = ({
  settings,
  onChange,
  onSave
}) => {
  const updateSetting = (key, value) => {
    onChange({
      ...settings,
      [key]: value
    });
  };
  const toggleHeadingLevel = level => {
    const levels = settings.default_heading_levels || [];
    if (levels.includes(level)) {
      updateSetting('default_heading_levels', levels.filter(l => l !== level));
    } else {
      updateSetting('default_heading_levels', [...levels, level]);
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Module Settings"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Configure how internal links are applied to your content."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", {
    className: "settings-section__title"
  }, "Global Defaults"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "default-max-links"
  }, "Default max links per page"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "0 means no limit.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "default-max-links",
    type: "number",
    min: "0",
    max: "50",
    value: settings.default_max_links_per_page || 0,
    onChange: e => updateSetting('default_max_links_per_page', parseInt(e.target.value, 10) || 0),
    style: {
      width: '80px'
    }
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Default heading behavior"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Control whether links can appear in headings.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "radio-group"
  }, ['none', 'selected', 'all'].map(opt => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    key: opt,
    className: "radio-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "heading_behavior",
    value: opt,
    checked: settings.default_heading_behavior === opt,
    onChange: () => updateSetting('default_heading_behavior', opt)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, opt.charAt(0).toUpperCase() + opt.slice(1))))), settings.default_heading_behavior === 'selected' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "checkbox-group",
    style: {
      marginTop: '8px'
    }
  }, HEADING_LEVELS.map(level => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    key: level,
    className: "checkbox-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: (settings.default_heading_levels || []).includes(level),
    onChange: () => toggleHeadingLevel(level)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, level.toUpperCase()))))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", {
    className: "settings-section__title"
  }, "Safeties"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "avoid-existing"
  }, "Avoid replacing inside existing links")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "avoid-existing",
    type: "checkbox",
    checked: settings.avoid_existing_links,
    onChange: e => updateSetting('avoid_existing_links', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "word-boundaries"
  }, "Prefer word boundaries")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "word-boundaries",
    type: "checkbox",
    checked: settings.prefer_word_boundaries,
    onChange: e => updateSetting('prefer_word_boundaries', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "normalize-accents"
  }, "Normalize accents/diacritics")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "normalize-accents",
    type: "checkbox",
    checked: settings.normalize_accents,
    onChange: e => updateSetting('normalize_accents', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", {
    className: "settings-section__title"
  }, "Performance"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "cache-content"
  }, "Cache rendered content")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "cache-content",
    type: "checkbox",
    checked: settings.cache_rendered_content,
    onChange: e => updateSetting('cache_rendered_content', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "chunk-docs"
  }, "Chunk long documents"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Prevents timeouts on large content.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "chunk-docs",
    type: "checkbox",
    checked: settings.chunk_long_documents,
    onChange: e => updateSetting('chunk_long_documents', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "panel-footer"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: onSave
  }, "Save Settings")));
};

// Rule Modal Component - Simplified
const RuleModal = ({
  rule,
  categories,
  templates,
  onClose,
  onSave
}) => {
  const isEdit = !!rule;
  const [formData, setFormData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(() => {
    if (rule) {
      return {
        ...rule
      };
    }
    return {
      title: '',
      category: '',
      keywords: [],
      destination: {
        type: 'post',
        post: 0,
        url: ''
      },
      utm_template: 'inherit',
      attributes: {
        nofollow: false,
        new_tab: false
      },
      limits: {
        max_page: 1
      },
      status: 'active'
    };
  });
  const [keywordInput, setKeywordInput] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [postSearchQuery, setPostSearchQuery] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [postSearchResults, setPostSearchResults] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [selectedPost, setSelectedPost] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (rule && rule.destination?.type === 'post' && rule.destination?.post) {
      setSelectedPost({
        id: rule.destination.post,
        title: rule.destination_label || 'Loading...'
      });
    }
  }, [rule]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (postSearchQuery.length < 2) {
      setPostSearchResults([]);
      return;
    }
    const timer = setTimeout(async () => {
      try {
        const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
          path: `/wpseopilot/v2/internal-links/search-posts?search=${encodeURIComponent(postSearchQuery)}`
        });
        if (res.success) setPostSearchResults(res.data);
      } catch (err) {
        console.error('Post search failed:', err);
      }
    }, 300);
    return () => clearTimeout(timer);
  }, [postSearchQuery]);
  const updateFormData = (key, value) => setFormData(prev => ({
    ...prev,
    [key]: value
  }));
  const updateNested = (parent, key, value) => setFormData(prev => ({
    ...prev,
    [parent]: {
      ...prev[parent],
      [key]: value
    }
  }));
  const addKeyword = () => {
    const keyword = keywordInput.trim();
    if (keyword && !formData.keywords.includes(keyword)) {
      updateFormData('keywords', [...formData.keywords, keyword]);
      setKeywordInput('');
    }
  };
  const removeKeyword = keyword => updateFormData('keywords', formData.keywords.filter(k => k !== keyword));
  const handleKeywordKeyDown = e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addKeyword();
    }
  };
  const selectPost = post => {
    setSelectedPost(post);
    updateNested('destination', 'post', post.id);
    updateNested('destination', 'type', 'post');
    setPostSearchQuery('');
    setPostSearchResults([]);
  };
  const handleSubmit = async e => {
    e.preventDefault();
    setError('');
    setSaving(true);
    try {
      const path = isEdit ? `/wpseopilot/v2/internal-links/rules/${rule.id}` : '/wpseopilot/v2/internal-links/rules';
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path,
        method: isEdit ? 'PUT' : 'POST',
        data: formData
      });
      if (res.success) {
        onSave(res.data);
      } else {
        setError(res.message || 'Failed to save rule');
      }
    } catch (err) {
      setError(err.message || 'Failed to save rule');
    } finally {
      setSaving(false);
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal-overlay",
    onClick: onClose
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal modal--large",
    onClick: e => e.stopPropagation()
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, isEdit ? 'Edit Rule' : 'Add Rule'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "modal__close",
    onClick: onClose
  }, "\xD7")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("form", {
    onSubmit: handleSubmit
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal__body"
  }, error && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-error"
  }, error), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "rule-title"
  }, "Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "rule-title",
    type: "text",
    value: formData.title,
    onChange: e => updateFormData('title', e.target.value),
    placeholder: "e.g., Link to Services page",
    required: true
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "rule-category"
  }, "Category"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    id: "rule-category",
    value: formData.category,
    onChange: e => updateFormData('category', e.target.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: ""
  }, "None"), categories.map(cat => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: cat.id,
    value: cat.id
  }, cat.name)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Status"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: formData.status === 'active',
    onChange: e => updateFormData('status', e.target.checked ? 'active' : 'inactive')
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Destination"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "radio-group",
    style: {
      marginBottom: '12px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "radio-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "destination_type",
    checked: formData.destination.type === 'post',
    onChange: () => updateNested('destination', 'type', 'post')
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Post/Page")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "radio-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "destination_type",
    checked: formData.destination.type === 'url',
    onChange: () => updateNested('destination', 'type', 'url')
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Custom URL"))), formData.destination.type === 'post' ? selectedPost ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "selected-post"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, selectedPost.title), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "link-button",
    onClick: () => {
      setSelectedPost(null);
      updateNested('destination', 'post', 0);
    }
  }, "Change")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "post-search"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    placeholder: "Search posts...",
    value: postSearchQuery,
    onChange: e => setPostSearchQuery(e.target.value)
  }), postSearchResults.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
    className: "post-search__results"
  }, postSearchResults.map(post => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    key: post.id
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    onClick: () => selectPost(post)
  }, post.title, " ", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "muted"
  }, "(", post.post_type, ")")))))) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "url",
    value: formData.destination.url,
    onChange: e => updateNested('destination', 'url', e.target.value),
    placeholder: "https://example.com"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Keywords"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tag-input"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tag-input__tags"
  }, formData.keywords.map(keyword => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    key: keyword,
    className: "tag"
  }, keyword, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    onClick: () => removeKeyword(keyword)
  }, "\xD7")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    placeholder: "Type and press Enter",
    value: keywordInput,
    onChange: e => setKeywordInput(e.target.value),
    onKeyDown: handleKeywordKeyDown,
    onBlur: addKeyword
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field narrow"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "max-page"
  }, "Max per page"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "max-page",
    type: "number",
    min: "0",
    max: "50",
    value: formData.limits?.max_page || 1,
    onChange: e => updateNested('limits', 'max_page', parseInt(e.target.value, 10) || 1)
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Options"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "checkbox-group"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "checkbox-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: formData.attributes?.nofollow,
    onChange: e => updateNested('attributes', 'nofollow', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "nofollow")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "checkbox-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: formData.attributes?.new_tab,
    onChange: e => updateNested('attributes', 'new_tab', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "New tab")))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal__footer"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: onClose
  }, "Cancel"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "submit",
    className: "button primary",
    disabled: saving
  }, saving ? 'Saving...' : isEdit ? 'Update' : 'Create')))));
};

// Category Modal Component
const CategoryModal = ({
  category,
  templates,
  onClose,
  onSave
}) => {
  const isEdit = !!category;
  const [formData, setFormData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(() => {
    if (category) {
      return {
        ...category
      };
    }
    return {
      name: '',
      color: '#4F46E5',
      description: '',
      default_utm: '',
      category_cap: 0
    };
  });
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const handleSubmit = async e => {
    e.preventDefault();
    setError('');
    setSaving(true);
    try {
      const path = isEdit ? `/wpseopilot/v2/internal-links/categories/${category.id}` : '/wpseopilot/v2/internal-links/categories';
      const method = isEdit ? 'PUT' : 'POST';
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path,
        method,
        data: formData
      });
      if (res.success) {
        onSave(res.data);
      } else {
        setError(res.message || 'Failed to save category');
      }
    } catch (err) {
      setError(err.message || 'Failed to save category');
    } finally {
      setSaving(false);
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal-overlay",
    onClick: onClose
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal",
    onClick: e => e.stopPropagation()
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, isEdit ? 'Edit Category' : 'Add Category'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "modal__close",
    onClick: onClose
  }, "\xD7")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("form", {
    onSubmit: handleSubmit
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal__body"
  }, error && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-error"
  }, error), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "cat-name"
  }, "Name"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "cat-name",
    type: "text",
    value: formData.name,
    onChange: e => setFormData({
      ...formData,
      name: e.target.value
    }),
    required: true
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "cat-color"
  }, "Color"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "cat-color",
    type: "color",
    value: formData.color,
    onChange: e => setFormData({
      ...formData,
      color: e.target.value
    })
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "cat-desc"
  }, "Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    id: "cat-desc",
    rows: "3",
    value: formData.description,
    onChange: e => setFormData({
      ...formData,
      description: e.target.value
    })
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "cat-utm"
  }, "Default UTM Template"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    id: "cat-utm",
    value: formData.default_utm,
    onChange: e => setFormData({
      ...formData,
      default_utm: e.target.value
    })
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: ""
  }, "None"), templates.map(tpl => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: tpl.id,
    value: tpl.id
  }, tpl.name)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "cat-cap"
  }, "Category-level cap (per page)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "cat-cap",
    type: "number",
    min: "0",
    max: "50",
    value: formData.category_cap || '',
    onChange: e => setFormData({
      ...formData,
      category_cap: parseInt(e.target.value, 10) || 0
    }),
    placeholder: "0 = no extra cap"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal__footer"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: onClose
  }, "Cancel"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "submit",
    className: "button primary",
    disabled: saving
  }, saving ? 'Saving...' : isEdit ? 'Update Category' : 'Create Category')))));
};

// Template Modal Component - Simplified
const TemplateModal = ({
  template,
  onClose,
  onSave
}) => {
  const isEdit = !!template;
  const [formData, setFormData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(() => template ? {
    ...template
  } : {
    name: '',
    utm_source: '',
    utm_medium: '',
    utm_campaign: '',
    apply_to: 'both'
  });
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const handleSubmit = async e => {
    e.preventDefault();
    setError('');
    setSaving(true);
    try {
      const path = isEdit ? `/wpseopilot/v2/internal-links/templates/${template.id}` : '/wpseopilot/v2/internal-links/templates';
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path,
        method: isEdit ? 'PUT' : 'POST',
        data: formData
      });
      if (res.success) {
        onSave(res.data);
      } else {
        setError(res.message || 'Failed to save template');
      }
    } catch (err) {
      setError(err.message || 'Failed to save template');
    } finally {
      setSaving(false);
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal-overlay",
    onClick: onClose
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal",
    onClick: e => e.stopPropagation()
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, isEdit ? 'Edit Template' : 'Add Template'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "modal__close",
    onClick: onClose
  }, "\xD7")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("form", {
    onSubmit: handleSubmit
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal__body"
  }, error && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-error"
  }, error), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "tpl-name"
  }, "Name"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "tpl-name",
    type: "text",
    value: formData.name,
    onChange: e => setFormData({
      ...formData,
      name: e.target.value
    }),
    required: true
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "tpl-source"
  }, "utm_source"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "tpl-source",
    type: "text",
    value: formData.utm_source || '',
    onChange: e => setFormData({
      ...formData,
      utm_source: e.target.value
    }),
    placeholder: "e.g., website"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "tpl-medium"
  }, "utm_medium"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "tpl-medium",
    type: "text",
    value: formData.utm_medium || '',
    onChange: e => setFormData({
      ...formData,
      utm_medium: e.target.value
    }),
    placeholder: "e.g., internal_link"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "tpl-campaign"
  }, "utm_campaign"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "tpl-campaign",
    type: "text",
    value: formData.utm_campaign || '',
    onChange: e => setFormData({
      ...formData,
      utm_campaign: e.target.value
    }),
    placeholder: "e.g., cross_selling"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Apply to"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "radio-group"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "radio-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "apply_to",
    checked: formData.apply_to === 'internal',
    onChange: () => setFormData({
      ...formData,
      apply_to: 'internal'
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Internal")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "radio-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "apply_to",
    checked: formData.apply_to === 'external',
    onChange: () => setFormData({
      ...formData,
      apply_to: 'external'
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "External")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "radio-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "apply_to",
    checked: formData.apply_to === 'both',
    onChange: () => setFormData({
      ...formData,
      apply_to: 'both'
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Both"))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modal__footer"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: onClose
  }, "Cancel"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "submit",
    className: "button primary",
    disabled: saving
  }, saving ? 'Saving...' : isEdit ? 'Update' : 'Create')))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (InternalLinking);

/***/ },

/***/ "./src-v2/pages/Log404.js"
/*!********************************!*\
  !*** ./src-v2/pages/Log404.js ***!
  \********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);



const SORT_OPTIONS = [{
  value: 'recent',
  label: 'Most recent'
}, {
  value: 'top',
  label: 'Top hits'
}];
const PER_PAGE_OPTIONS = [25, 50, 100, 200];
const Log404 = ({
  onNavigate
}) => {
  // 404 Log state
  const [logEntries, setLogEntries] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [logLoading, setLogLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [logTotal, setLogTotal] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(0);
  const [logPage, setLogPage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(1);
  const [logPerPage, setLogPerPage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(50);
  const [logTotalPages, setLogTotalPages] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(1);
  const [logSort, setLogSort] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('recent');
  const [hideSpam, setHideSpam] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [hideImages, setHideImages] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [clearingLog, setClearingLog] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);

  // Fetch 404 log
  const fetchLog = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setLogLoading(true);
    try {
      const params = new URLSearchParams({
        sort: logSort,
        per_page: logPerPage,
        page: logPage,
        hide_spam: hideSpam ? '1' : '0',
        hide_images: hideImages ? '1' : '0'
      });
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/404-log?${params}`
      });
      if (response.success) {
        setLogEntries(response.data.items);
        setLogTotal(response.data.total);
        setLogTotalPages(response.data.total_pages);
      }
    } catch (error) {
      console.error('Failed to fetch 404 log:', error);
    } finally {
      setLogLoading(false);
    }
  }, [logSort, logPerPage, logPage, hideSpam, hideImages]);

  // Load data on mount
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchLog();
  }, [fetchLog]);

  // Create redirect from 404 entry - navigate to redirects page
  const handleCreateFrom404 = entry => {
    // Store the source URL in sessionStorage so Redirects page can pick it up
    sessionStorage.setItem('wpseopilot_redirect_source', entry.request_uri);
    if (onNavigate) {
      onNavigate('redirects');
    }
  };

  // Clear 404 log
  const handleClearLog = async () => {
    if (!window.confirm('Are you sure you want to clear the entire 404 log? This cannot be undone.')) {
      return;
    }
    setClearingLog(true);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/404-log',
        method: 'DELETE'
      });
      setLogEntries([]);
      setLogTotal(0);
      setLogTotalPages(1);
      setLogPage(1);
    } catch (error) {
      console.error('Failed to clear log:', error);
    } finally {
      setClearingLog(false);
    }
  };

  // Format date
  const formatDate = dateStr => {
    if (!dateStr || dateStr === '0000-00-00 00:00:00') return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString() + ', ' + date.toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit'
    });
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "404 Log"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Monitor broken links and create redirects to fix them.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "header-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: handleClearLog,
    disabled: clearingLog || logEntries.length === 0
  }, clearingLog ? 'Clearing...' : 'Clear Log'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stats-bar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-box"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-box__value"
  }, logTotal.toLocaleString()), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-box__label"
  }, "Total Entries")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-box"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-box__value"
  }, logEntries.filter(e => !e.redirect_exists).length), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-box__label"
  }, "Need Redirect")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "filter-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "filter-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "filter-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Sort by"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: logSort,
    onChange: e => {
      setLogSort(e.target.value);
      setLogPage(1);
    }
  }, SORT_OPTIONS.map(opt => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: opt.value,
    value: opt.value
  }, opt.label)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "filter-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Rows per page"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: logPerPage,
    onChange: e => {
      setLogPerPage(parseInt(e.target.value, 10));
      setLogPage(1);
    }
  }, PER_PAGE_OPTIONS.map(opt => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: opt,
    value: opt
  }, opt)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "filter-checkbox"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: hideSpam,
    onChange: e => {
      setHideSpam(e.target.checked);
      setLogPage(1);
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Hide spammy extensions")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "filter-checkbox"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: hideImages,
    onChange: e => {
      setHideImages(e.target.checked);
      setLogPage(1);
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Hide image extensions")))), logLoading ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "loading-state"
  }, "Loading 404 log...") : logEntries.length === 0 ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "empty-state"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "empty-state__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "1.5",
    width: "48",
    height: "48"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "10"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 9l6 6m0-6l-6 6"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "No 404 errors logged"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Great news! Your site doesn't have any broken links recorded yet.")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "data-table"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Request URL"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Hits"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Last Seen"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Device"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Action"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, logEntries.map(entry => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
    key: entry.id
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
    className: "url-cell"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", null, entry.request_uri), entry.redirect_exists && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "badge success"
  }, "Redirect exists")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `hits-badge ${entry.hits > 10 ? 'high' : entry.hits > 5 ? 'medium' : ''}`
  }, entry.hits)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, formatDate(entry.last_seen)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, entry.device_label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, !entry.redirect_exists ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary small",
    onClick: () => handleCreateFrom404(entry)
  }, "Create Redirect") : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "muted"
  }, "-")))))), logTotalPages > 1 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pagination"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "pagination-info"
  }, logTotal.toLocaleString(), " ", logTotal === 1 ? 'item' : 'items'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pagination-links"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "pagination-btn",
    disabled: logPage <= 1,
    onClick: () => setLogPage(logPage - 1)
  }, "\u2039 Previous"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "pagination-current"
  }, logPage, " of ", logTotalPages), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "pagination-btn",
    disabled: logPage >= logTotalPages,
    onClick: () => setLogPage(logPage + 1)
  }, "Next \u203A"))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Log404);

/***/ },

/***/ "./src-v2/pages/More.js"
/*!******************************!*\
  !*** ./src-v2/pages/More.js ***!
  \******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const More = () => {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "More from Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Expand your WordPress toolkit with trusted companion plugins from the Pilot family.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://github.com/jhd3197",
    target: "_blank",
    rel: "noopener noreferrer",
    className: "button ghost"
  }, "View All Plugins")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-grid"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card active"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-head"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-identity"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "pilot-card-mark seo",
    "aria-hidden": "true"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    role: "img",
    focusable: "false"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-title"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "WP SEO Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "badge success"
  }, "Installed")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "pilot-card-tagline"
  }, "Performance-led SEO insights."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    defaultChecked: true
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-text"
  }, "Enabled"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "pilot-card-desc"
  }, "Actionable SEO guidance, audits, sitemaps, redirects, and ranking insights."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-meta"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "pill success"
  }, "Active"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://github.com/jhd3197/WP-SEO-Pilot",
    target: "_blank",
    rel: "noopener noreferrer",
    className: "pilot-card-link"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    width: "16",
    height: "16",
    fill: "currentColor"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2C6.5 2 2 6.6 2 12.3c0 4.6 2.9 8.5 6.9 9.9.5.1.7-.2.7-.5v-1.9c-2.8.6-3.3-1.2-3.3-1.2-.5-1.2-1.2-1.5-1.2-1.5-1-.7.1-.7.1-.7 1.1.1 1.7 1.2 1.7 1.2 1 .1.7 1.7 2.6 1.2.1-.8.4-1.2.7-1.5-2.2-.2-4.5-1.2-4.5-5.2 0-1.1.4-2 1-2.7-.1-.2-.4-1.3.1-2.7 0 0 .8-.2 2.7 1a9.2 9.2 0 0 1 4.9 0c1.9-1.2 2.7-1 2.7-1 .5 1.4.2 2.5.1 2.7.6.7 1 1.6 1 2.7 0 4-2.3 5-4.5 5.2.4.3.8 1 .8 2.1v3c0 .3.2.6.7.5 4-1.4 6.9-5.3 6.9-9.9C22 6.6 17.5 2 12 2z"
  })), "GitHub"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-head"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-identity"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "pilot-card-mark ai",
    "aria-hidden": "true"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    role: "img",
    focusable: "false"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "1"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "8",
    cy: "12",
    r: "1"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "16",
    cy: "12",
    r: "1"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-title"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "WP AI Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "badge"
  }, "Available")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "pilot-card-tagline"
  }, "Centralized AI management.")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "pilot-card-desc"
  }, "A unified AI interface for WordPress. Manage models, track usage, and let all your plugins leverage AI through a single hub."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-meta"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "pill warning"
  }, "Not Installed"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://github.com/jhd3197/WP-AI-Pilot",
    target: "_blank",
    rel: "noopener noreferrer",
    className: "button primary"
  }, "Get Plugin"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-head"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-identity"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "pilot-card-mark security",
    "aria-hidden": "true"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    role: "img",
    focusable: "false"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2L4 5.4v6.2c0 5.1 3.4 9.7 8 10.4 4.6-.7 8-5.3 8-10.4V5.4L12 2zm0 2.2l6 2.3v5.1c0 4-2.5 7.6-6 8.3-3.5-.7-6-4.3-6-8.3V6.5l6-2.3z"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M10.5 12.7l-2-2-1.3 1.3 3.3 3.3 5.3-5.3-1.3-1.3-4 4z"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-title"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "WP Security Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "badge"
  }, "Available")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "pilot-card-tagline"
  }, "Open standard security.")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "pilot-card-desc"
  }, "Core security suite with firewall, malware scans, login protection, and hardening controls."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pilot-card-meta"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "pill warning"
  }, "Not Installed"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://github.com/jhd3197/WP-Security-Pilot",
    target: "_blank",
    rel: "noopener noreferrer",
    className: "button primary"
  }, "Get Plugin")))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (More);

/***/ },

/***/ "./src-v2/pages/Redirects.js"
/*!***********************************!*\
  !*** ./src-v2/pages/Redirects.js ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);



const STATUS_CODES = [{
  value: 301,
  label: '301 Permanent'
}, {
  value: 302,
  label: '302 Temporary'
}, {
  value: 307,
  label: '307'
}, {
  value: 410,
  label: '410 Gone'
}];
const Redirects = () => {
  // Redirects state
  const [redirects, setRedirects] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [redirectsLoading, setRedirectsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [newSource, setNewSource] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [newTarget, setNewTarget] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [newStatusCode, setNewStatusCode] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(301);
  const [createLoading, setCreateLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [createError, setCreateError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');

  // Slug suggestions state
  const [suggestions, setSuggestions] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [suggestionsLoading, setSuggestionsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);

  // Fetch redirects
  const fetchRedirects = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setRedirectsLoading(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/redirects'
      });
      if (response.success) {
        setRedirects(response.data);
      }
    } catch (error) {
      console.error('Failed to fetch redirects:', error);
    } finally {
      setRedirectsLoading(false);
    }
  }, []);

  // Fetch slug suggestions
  const fetchSuggestions = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setSuggestionsLoading(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/slug-suggestions'
      });
      if (response.success) {
        setSuggestions(response.data);
      }
    } catch (error) {
      console.error('Failed to fetch suggestions:', error);
    } finally {
      setSuggestionsLoading(false);
    }
  }, []);

  // Load data on mount
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchRedirects();
    fetchSuggestions();

    // Check if there's a redirect source from 404 Log page
    const storedSource = sessionStorage.getItem('wpseopilot_redirect_source');
    if (storedSource) {
      setNewSource(storedSource);
      sessionStorage.removeItem('wpseopilot_redirect_source');
      // Focus the target field
      setTimeout(() => {
        document.getElementById('redirect-target')?.focus();
      }, 100);
    }
  }, [fetchRedirects, fetchSuggestions]);

  // Create redirect
  const handleCreateRedirect = async e => {
    e.preventDefault();
    setCreateError('');
    setCreateLoading(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/redirects',
        method: 'POST',
        data: {
          source: newSource,
          target: newTarget,
          status_code: newStatusCode
        }
      });
      if (response.success) {
        setRedirects([response.data, ...redirects]);
        setNewSource('');
        setNewTarget('');
        setNewStatusCode(301);
        // Refetch suggestions in case one was auto-removed
        fetchSuggestions();
      } else {
        setCreateError(response.message || 'Failed to create redirect');
      }
    } catch (error) {
      setCreateError(error.message || 'Failed to create redirect');
    } finally {
      setCreateLoading(false);
    }
  };

  // Delete redirect
  const handleDeleteRedirect = async id => {
    if (!window.confirm('Are you sure you want to delete this redirect?')) {
      return;
    }
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/redirects/${id}`,
        method: 'DELETE'
      });
      setRedirects(redirects.filter(r => r.id !== id));
    } catch (error) {
      console.error('Failed to delete redirect:', error);
    }
  };

  // Apply slug suggestion
  const handleApplySuggestion = async key => {
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/slug-suggestions/${key}/apply`,
        method: 'POST'
      });
      if (response.success) {
        setSuggestions(suggestions.filter(s => s.key !== key));
        fetchRedirects();
      }
    } catch (error) {
      console.error('Failed to apply suggestion:', error);
    }
  };

  // Dismiss slug suggestion
  const handleDismissSuggestion = async key => {
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/slug-suggestions/${key}/dismiss`,
        method: 'POST'
      });
      setSuggestions(suggestions.filter(s => s.key !== key));
    } catch (error) {
      console.error('Failed to dismiss suggestion:', error);
    }
  };

  // Use suggestion to prefill form
  const handleUseSuggestion = suggestion => {
    setNewSource(suggestion.source);
    setNewTarget(suggestion.target);
    setNewStatusCode(301);
    // Scroll to form
    document.getElementById('redirect-source')?.focus();
  };

  // Format date
  const formatDate = dateStr => {
    if (!dateStr || dateStr === '0000-00-00 00:00:00') return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString() + ', ' + date.toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit'
    });
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Redirects"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Create and manage URL redirects to maintain SEO value when URLs change.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost"
  }, "Import Redirects")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, !suggestionsLoading && suggestions.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "alert-card warning",
    style: {
      marginBottom: '24px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "alert-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Detected Slug Changes")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "The following posts have changed their URL structure. Create redirects to prevent 404 errors."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "data-table suggestions-table"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Old Path"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "New Target"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Actions"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, suggestions.map(suggestion => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
    key: suggestion.key
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", null, suggestion.source)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: suggestion.target,
    target: "_blank",
    rel: "noopener noreferrer"
  }, suggestion.target)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
    className: "action-buttons"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary small",
    onClick: () => handleApplySuggestion(suggestion.key)
  }, "Apply"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost small",
    onClick: () => handleUseSuggestion(suggestion)
  }, "Use"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "link-button danger",
    onClick: () => handleDismissSuggestion(suggestion.key)
  }, "Dismiss"))))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Active Redirects"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, redirects.length, " redirect", redirects.length !== 1 ? 's' : '', " configured."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("form", {
    onSubmit: handleCreateRedirect,
    className: "redirect-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "redirect-source"
  }, "Source Path"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    id: "redirect-source",
    placeholder: "/old-url",
    value: newSource,
    onChange: e => setNewSource(e.target.value),
    required: true
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "redirect-target"
  }, "Target URL"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "url",
    id: "redirect-target",
    placeholder: "https://example.com/new-url",
    value: newTarget,
    onChange: e => setNewTarget(e.target.value),
    required: true
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field narrow"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "redirect-status"
  }, "Status"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    id: "redirect-status",
    value: newStatusCode,
    onChange: e => setNewStatusCode(parseInt(e.target.value, 10))
  }, STATUS_CODES.map(code => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: code.value,
    value: code.value
  }, code.label)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-field button-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "submit",
    className: "button primary",
    disabled: createLoading
  }, createLoading ? 'Adding...' : 'Add Redirect'))), createError && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "form-error"
  }, createError)), redirectsLoading ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "loading-state"
  }, "Loading redirects...") : redirects.length === 0 ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "empty-state"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "empty-state__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "1.5",
    width: "48",
    height: "48"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 18l6-6-6-6"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M15 6l-6 6 6 6",
    opacity: "0.5"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "No redirects configured"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Add your first redirect using the form above.")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "data-table"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Source"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Target"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Status"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Hits"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Last Hit"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Action"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, redirects.map(redirect => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
    key: redirect.id
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", null, redirect.source)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: redirect.target,
    target: "_blank",
    rel: "noopener noreferrer"
  }, redirect.target)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `pill ${redirect.status_code === 301 ? 'success' : 'warning'}`
  }, redirect.status_code)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, redirect.hits), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, formatDate(redirect.last_hit)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "link-button danger",
    onClick: () => handleDeleteRedirect(redirect.id)
  }, "Delete"))))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Redirects);

/***/ },

/***/ "./src-v2/pages/SchemaBuilder.js"
/*!***************************************!*\
  !*** ./src-v2/pages/SchemaBuilder.js ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);



const schemaTypes = [{
  id: 'Article',
  name: 'Article',
  icon: '📄',
  description: 'News, blog posts, and articles'
}, {
  id: 'Product',
  name: 'Product',
  icon: '🛍️',
  description: 'E-commerce products'
}, {
  id: 'LocalBusiness',
  name: 'Local Business',
  icon: '🏪',
  description: 'Physical business location'
}, {
  id: 'FAQPage',
  name: 'FAQ Page',
  icon: '❓',
  description: 'Frequently asked questions'
}, {
  id: 'HowTo',
  name: 'How To',
  icon: '📝',
  description: 'Step-by-step instructions'
}, {
  id: 'Recipe',
  name: 'Recipe',
  icon: '🍳',
  description: 'Cooking recipes'
}, {
  id: 'Event',
  name: 'Event',
  icon: '📅',
  description: 'Events and conferences'
}, {
  id: 'Person',
  name: 'Person',
  icon: '👤',
  description: 'Author or person profile'
}, {
  id: 'Organization',
  name: 'Organization',
  icon: '🏢',
  description: 'Company or organization'
}, {
  id: 'WebSite',
  name: 'Website',
  icon: '🌐',
  description: 'Website information'
}, {
  id: 'BreadcrumbList',
  name: 'Breadcrumbs',
  icon: '🔗',
  description: 'Navigation breadcrumbs'
}, {
  id: 'VideoObject',
  name: 'Video',
  icon: '🎬',
  description: 'Video content'
}];
const schemaFields = {
  Article: [{
    key: 'headline',
    label: 'Headline',
    type: 'text',
    required: true
  }, {
    key: 'description',
    label: 'Description',
    type: 'textarea'
  }, {
    key: 'author',
    label: 'Author Name',
    type: 'text'
  }, {
    key: 'datePublished',
    label: 'Date Published',
    type: 'date'
  }, {
    key: 'dateModified',
    label: 'Date Modified',
    type: 'date'
  }, {
    key: 'image',
    label: 'Image URL',
    type: 'url'
  }],
  Product: [{
    key: 'name',
    label: 'Product Name',
    type: 'text',
    required: true
  }, {
    key: 'description',
    label: 'Description',
    type: 'textarea'
  }, {
    key: 'image',
    label: 'Image URL',
    type: 'url'
  }, {
    key: 'brand',
    label: 'Brand',
    type: 'text'
  }, {
    key: 'sku',
    label: 'SKU',
    type: 'text'
  }, {
    key: 'price',
    label: 'Price',
    type: 'number'
  }, {
    key: 'priceCurrency',
    label: 'Currency',
    type: 'text',
    placeholder: 'USD'
  }, {
    key: 'availability',
    label: 'Availability',
    type: 'select',
    options: ['InStock', 'OutOfStock', 'PreOrder']
  }],
  LocalBusiness: [{
    key: 'name',
    label: 'Business Name',
    type: 'text',
    required: true
  }, {
    key: 'description',
    label: 'Description',
    type: 'textarea'
  }, {
    key: 'image',
    label: 'Image URL',
    type: 'url'
  }, {
    key: 'telephone',
    label: 'Phone',
    type: 'tel'
  }, {
    key: 'streetAddress',
    label: 'Street Address',
    type: 'text'
  }, {
    key: 'addressLocality',
    label: 'City',
    type: 'text'
  }, {
    key: 'addressRegion',
    label: 'State/Region',
    type: 'text'
  }, {
    key: 'postalCode',
    label: 'Postal Code',
    type: 'text'
  }, {
    key: 'addressCountry',
    label: 'Country',
    type: 'text'
  }],
  FAQPage: [{
    key: 'faqs',
    label: 'FAQ Items',
    type: 'faq-list'
  }],
  HowTo: [{
    key: 'name',
    label: 'Title',
    type: 'text',
    required: true
  }, {
    key: 'description',
    label: 'Description',
    type: 'textarea'
  }, {
    key: 'totalTime',
    label: 'Total Time',
    type: 'text',
    placeholder: 'PT30M (30 minutes)'
  }, {
    key: 'steps',
    label: 'Steps',
    type: 'steps-list'
  }],
  Recipe: [{
    key: 'name',
    label: 'Recipe Name',
    type: 'text',
    required: true
  }, {
    key: 'description',
    label: 'Description',
    type: 'textarea'
  }, {
    key: 'image',
    label: 'Image URL',
    type: 'url'
  }, {
    key: 'prepTime',
    label: 'Prep Time',
    type: 'text',
    placeholder: 'PT15M'
  }, {
    key: 'cookTime',
    label: 'Cook Time',
    type: 'text',
    placeholder: 'PT30M'
  }, {
    key: 'recipeYield',
    label: 'Servings',
    type: 'text'
  }, {
    key: 'recipeIngredient',
    label: 'Ingredients',
    type: 'list'
  }, {
    key: 'recipeInstructions',
    label: 'Instructions',
    type: 'steps-list'
  }],
  Event: [{
    key: 'name',
    label: 'Event Name',
    type: 'text',
    required: true
  }, {
    key: 'description',
    label: 'Description',
    type: 'textarea'
  }, {
    key: 'startDate',
    label: 'Start Date',
    type: 'datetime-local'
  }, {
    key: 'endDate',
    label: 'End Date',
    type: 'datetime-local'
  }, {
    key: 'location',
    label: 'Location Name',
    type: 'text'
  }, {
    key: 'streetAddress',
    label: 'Address',
    type: 'text'
  }, {
    key: 'image',
    label: 'Image URL',
    type: 'url'
  }],
  Person: [{
    key: 'name',
    label: 'Name',
    type: 'text',
    required: true
  }, {
    key: 'jobTitle',
    label: 'Job Title',
    type: 'text'
  }, {
    key: 'description',
    label: 'Bio',
    type: 'textarea'
  }, {
    key: 'image',
    label: 'Photo URL',
    type: 'url'
  }, {
    key: 'email',
    label: 'Email',
    type: 'email'
  }, {
    key: 'url',
    label: 'Website',
    type: 'url'
  }],
  Organization: [{
    key: 'name',
    label: 'Organization Name',
    type: 'text',
    required: true
  }, {
    key: 'description',
    label: 'Description',
    type: 'textarea'
  }, {
    key: 'logo',
    label: 'Logo URL',
    type: 'url'
  }, {
    key: 'url',
    label: 'Website',
    type: 'url'
  }, {
    key: 'telephone',
    label: 'Phone',
    type: 'tel'
  }, {
    key: 'email',
    label: 'Email',
    type: 'email'
  }],
  WebSite: [{
    key: 'name',
    label: 'Site Name',
    type: 'text',
    required: true
  }, {
    key: 'url',
    label: 'URL',
    type: 'url',
    required: true
  }, {
    key: 'description',
    label: 'Description',
    type: 'textarea'
  }, {
    key: 'potentialAction',
    label: 'Enable Site Search',
    type: 'checkbox'
  }],
  BreadcrumbList: [{
    key: 'items',
    label: 'Breadcrumb Items',
    type: 'breadcrumb-list'
  }],
  VideoObject: [{
    key: 'name',
    label: 'Video Title',
    type: 'text',
    required: true
  }, {
    key: 'description',
    label: 'Description',
    type: 'textarea'
  }, {
    key: 'thumbnailUrl',
    label: 'Thumbnail URL',
    type: 'url'
  }, {
    key: 'uploadDate',
    label: 'Upload Date',
    type: 'date'
  }, {
    key: 'duration',
    label: 'Duration',
    type: 'text',
    placeholder: 'PT5M30S'
  }, {
    key: 'contentUrl',
    label: 'Video URL',
    type: 'url'
  }, {
    key: 'embedUrl',
    label: 'Embed URL',
    type: 'url'
  }]
};
const SchemaBuilder = ({
  onNavigate
}) => {
  const [selectedType, setSelectedType] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [formData, setFormData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const [generatedSchema, setGeneratedSchema] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [validation, setValidation] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [generating, setGenerating] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [validating, setValidating] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [postUrl, setPostUrl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [detecting, setDetecting] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [copied, setCopied] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const handleTypeSelect = type => {
    setSelectedType(type);
    setFormData({});
    setGeneratedSchema(null);
    setValidation(null);
  };
  const handleFieldChange = (key, value) => {
    setFormData(prev => ({
      ...prev,
      [key]: value
    }));
  };
  const handleDetectSchema = async () => {
    if (!postUrl) return;
    setDetecting(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/tools/schema/detect',
        method: 'POST',
        data: {
          url: postUrl
        }
      });
      if (response.success && response.data.suggested_type) {
        setSelectedType(response.data.suggested_type);
        if (response.data.prefilled_data) {
          setFormData(response.data.prefilled_data);
        }
      }
    } catch (error) {
      console.error('Failed to detect:', error);
    } finally {
      setDetecting(false);
    }
  };
  const handleGenerateSchema = async () => {
    if (!selectedType) return;
    setGenerating(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/tools/schema/generate',
        method: 'POST',
        data: {
          type: selectedType,
          data: formData
        }
      });
      if (response.success) {
        setGeneratedSchema(response.data.schema);
      }
    } catch (error) {
      console.error('Failed to generate:', error);
    } finally {
      setGenerating(false);
    }
  };
  const handleValidate = async () => {
    if (!generatedSchema) return;
    setValidating(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/tools/schema/validate',
        method: 'POST',
        data: {
          schema: generatedSchema
        }
      });
      if (response.success) {
        setValidation(response.data);
      }
    } catch (error) {
      console.error('Failed to validate:', error);
    } finally {
      setValidating(false);
    }
  };
  const handleCopySchema = () => {
    const schemaScript = `<script type="application/ld+json">\n${JSON.stringify(generatedSchema, null, 2)}\n</script>`;
    navigator.clipboard.writeText(schemaScript);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };
  const renderField = field => {
    const value = formData[field.key] || '';
    switch (field.type) {
      case 'textarea':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
          value: value,
          onChange: e => handleFieldChange(field.key, e.target.value),
          placeholder: field.placeholder || '',
          rows: 3
        });
      case 'select':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
          value: value,
          onChange: e => handleFieldChange(field.key, e.target.value)
        }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
          value: ""
        }, "Select..."), field.options.map(opt => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
          key: opt,
          value: opt
        }, opt)));
      case 'checkbox':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
          className: "checkbox-label"
        }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
          type: "checkbox",
          checked: !!value,
          onChange: e => handleFieldChange(field.key, e.target.checked)
        }), "Enable");
      case 'faq-list':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(FAQListField, {
          value: value || [],
          onChange: v => handleFieldChange(field.key, v)
        });
      case 'steps-list':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(StepsListField, {
          value: value || [],
          onChange: v => handleFieldChange(field.key, v)
        });
      case 'list':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(SimpleListField, {
          value: value || [],
          onChange: v => handleFieldChange(field.key, v)
        });
      case 'breadcrumb-list':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(BreadcrumbListField, {
          value: value || [],
          onChange: v => handleFieldChange(field.key, v)
        });
      default:
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
          type: field.type,
          value: value,
          onChange: e => handleFieldChange(field.key, e.target.value),
          placeholder: field.placeholder || ''
        });
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page schema-builder-page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header__breadcrumb"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "breadcrumb-link",
    onClick: () => onNavigate('tools')
  }, "Tools"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breadcrumb-separator"
  }, "/"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Schema Builder")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Visual Schema Builder"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Create structured data markup for rich search results."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "schema-builder-layout"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "schema-builder-main"
  }, !selectedType ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "schema-detect"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Auto-Detect from URL"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "detect-input"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "url",
    value: postUrl,
    onChange: e => setPostUrl(e.target.value),
    placeholder: "Enter a page URL to auto-detect schema type..."
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button--secondary",
    onClick: handleDetectSchema,
    disabled: detecting || !postUrl
  }, detecting ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "spinner-small"
  }) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "11",
    cy: "11",
    r: "8"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 21l-4.35-4.35"
  })), "Detect"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "schema-types"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Or Choose Schema Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "schema-types-grid"
  }, schemaTypes.map(type => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: type.id,
    type: "button",
    className: "schema-type-card",
    onClick: () => handleTypeSelect(type.id)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "schema-type-icon"
  }, type.icon), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "schema-type-name"
  }, type.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "schema-type-desc"
  }, type.description)))))) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "schema-form-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "back-button",
    onClick: () => {
      setSelectedType(null);
      setFormData({});
      setGeneratedSchema(null);
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M19 12H5M12 19l-7-7 7-7"
  })), "Back"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, schemaTypes.find(t => t.id === selectedType)?.icon, ' ', schemaTypes.find(t => t.id === selectedType)?.name, " Schema")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "schema-form"
  }, schemaFields[selectedType]?.map(field => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: field.key,
    className: "form-field"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, field.label, field.required && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "required"
  }, "*")), renderField(field))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button--primary",
    onClick: handleGenerateSchema,
    disabled: generating
  }, generating ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "spinner"
  }), "Generating...") : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "8",
    y: "2",
    width: "8",
    height: "4",
    rx: "1"
  })), "Generate Schema")))))), generatedSchema && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "schema-builder-preview"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "preview-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Generated Schema"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "preview-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button--small",
    onClick: handleValidate,
    disabled: validating
  }, validating ? 'Validating...' : 'Validate'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button--small button--primary",
    onClick: handleCopySchema
  }, copied ? 'Copied!' : 'Copy'))), validation && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `validation-result ${validation.valid ? 'valid' : 'invalid'}`
  }, validation.valid ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M22 11.08V12a10 10 0 11-5.93-9.14"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "22 4 12 14.01 9 11.01"
  })), "Valid schema markup") : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "16",
    height: "16"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "10"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "12",
    y1: "8",
    x2: "12",
    y2: "12"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "12",
    y1: "16",
    x2: "12.01",
    y2: "16"
  })), validation.errors?.length || 0, " issues found")), validation?.errors?.length > 0 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
    className: "validation-errors"
  }, validation.errors.map((err, idx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    key: idx
  }, err))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("pre", {
    className: "schema-code"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", null, JSON.stringify(generatedSchema, null, 2))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "preview-footer"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: `https://search.google.com/test/rich-results?url=${encodeURIComponent('data:application/ld+json,' + JSON.stringify(generatedSchema))}`,
    target: "_blank",
    rel: "noopener noreferrer",
    className: "button button--secondary"
  }, "Test in Google", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "14",
    height: "14"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("polyline", {
    points: "15 3 21 3 21 9"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "10",
    y1: "14",
    x2: "21",
    y2: "3"
  })))))));
};

// Sub-components for complex field types
const FAQListField = ({
  value,
  onChange
}) => {
  const addItem = () => {
    onChange([...value, {
      question: '',
      answer: ''
    }]);
  };
  const updateItem = (index, field, val) => {
    const newValue = [...value];
    newValue[index] = {
      ...newValue[index],
      [field]: val
    };
    onChange(newValue);
  };
  const removeItem = index => {
    onChange(value.filter((_, i) => i !== index));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "list-field"
  }, value.map((item, idx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: idx,
    className: "list-item faq-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    value: item.question,
    onChange: e => updateItem(idx, 'question', e.target.value),
    placeholder: "Question"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    value: item.answer,
    onChange: e => updateItem(idx, 'answer', e.target.value),
    placeholder: "Answer",
    rows: 2
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "remove-item",
    onClick: () => removeItem(idx)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "14",
    height: "14"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "18",
    y1: "6",
    x2: "6",
    y2: "18"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "6",
    y1: "6",
    x2: "18",
    y2: "18"
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "add-item",
    onClick: addItem
  }, "+ Add FAQ"));
};
const StepsListField = ({
  value,
  onChange
}) => {
  const addItem = () => {
    onChange([...value, {
      name: '',
      text: ''
    }]);
  };
  const updateItem = (index, field, val) => {
    const newValue = [...value];
    newValue[index] = {
      ...newValue[index],
      [field]: val
    };
    onChange(newValue);
  };
  const removeItem = index => {
    onChange(value.filter((_, i) => i !== index));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "list-field"
  }, value.map((item, idx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: idx,
    className: "list-item step-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "step-number"
  }, idx + 1), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "step-fields"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    value: item.name,
    onChange: e => updateItem(idx, 'name', e.target.value),
    placeholder: "Step title"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    value: item.text,
    onChange: e => updateItem(idx, 'text', e.target.value),
    placeholder: "Step description",
    rows: 2
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "remove-item",
    onClick: () => removeItem(idx)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "14",
    height: "14"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "18",
    y1: "6",
    x2: "6",
    y2: "18"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "6",
    y1: "6",
    x2: "18",
    y2: "18"
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "add-item",
    onClick: addItem
  }, "+ Add Step"));
};
const SimpleListField = ({
  value,
  onChange
}) => {
  const addItem = () => {
    onChange([...value, '']);
  };
  const updateItem = (index, val) => {
    const newValue = [...value];
    newValue[index] = val;
    onChange(newValue);
  };
  const removeItem = index => {
    onChange(value.filter((_, i) => i !== index));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "list-field"
  }, value.map((item, idx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: idx,
    className: "list-item simple-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    value: item,
    onChange: e => updateItem(idx, e.target.value),
    placeholder: `Item ${idx + 1}`
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "remove-item",
    onClick: () => removeItem(idx)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "14",
    height: "14"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "18",
    y1: "6",
    x2: "6",
    y2: "18"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "6",
    y1: "6",
    x2: "18",
    y2: "18"
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "add-item",
    onClick: addItem
  }, "+ Add Item"));
};
const BreadcrumbListField = ({
  value,
  onChange
}) => {
  const addItem = () => {
    onChange([...value, {
      name: '',
      url: ''
    }]);
  };
  const updateItem = (index, field, val) => {
    const newValue = [...value];
    newValue[index] = {
      ...newValue[index],
      [field]: val
    };
    onChange(newValue);
  };
  const removeItem = index => {
    onChange(value.filter((_, i) => i !== index));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "list-field"
  }, value.map((item, idx) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: idx,
    className: "list-item breadcrumb-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "breadcrumb-position"
  }, idx + 1), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    value: item.name,
    onChange: e => updateItem(idx, 'name', e.target.value),
    placeholder: "Page name"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "url",
    value: item.url,
    onChange: e => updateItem(idx, 'url', e.target.value),
    placeholder: "URL"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "remove-item",
    onClick: () => removeItem(idx)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2",
    width: "14",
    height: "14"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "18",
    y1: "6",
    x2: "6",
    y2: "18"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "6",
    y1: "6",
    x2: "18",
    y2: "18"
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "add-item",
    onClick: addItem
  }, "+ Add Breadcrumb"));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SchemaBuilder);

/***/ },

/***/ "./src-v2/pages/SearchAppearance.js"
/*!******************************************!*\
  !*** ./src-v2/pages/SearchAppearance.js ***!
  \******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _components_SubTabs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/SubTabs */ "./src-v2/components/SubTabs.js");
/* harmony import */ var _components_SearchPreview__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/SearchPreview */ "./src-v2/components/SearchPreview.js");
/* harmony import */ var _components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../components/TemplateInput */ "./src-v2/components/TemplateInput.js");
/* harmony import */ var _components_AiGenerateModal__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../components/AiGenerateModal */ "./src-v2/components/AiGenerateModal.js");
/* harmony import */ var _hooks_useUrlTab__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../hooks/useUrlTab */ "./src-v2/hooks/useUrlTab.js");









// Get AI status from global settings
const globalSettings = window?.wpseopilotV2Settings || {};
const aiEnabled = globalSettings.aiEnabled || false;
const aiProvider = globalSettings.aiProvider || 'none';
const aiPilot = globalSettings.aiPilot || null;
const searchAppearanceTabs = [{
  id: 'homepage',
  label: 'Homepage'
}, {
  id: 'content-types',
  label: 'Content Types'
}, {
  id: 'taxonomies',
  label: 'Taxonomies'
}, {
  id: 'archives',
  label: 'Archives'
}, {
  id: 'social-settings',
  label: 'Social Settings'
}, {
  id: 'social-cards',
  label: 'Social Cards'
}];

// Schema type options
const schemaTypeOptions = [{
  value: '',
  label: 'Use default (Article)'
}, {
  value: 'article',
  label: 'Article'
}, {
  value: 'blogposting',
  label: 'Blog posting'
}, {
  value: 'newsarticle',
  label: 'News article'
}, {
  value: 'product',
  label: 'Product'
}, {
  value: 'profilepage',
  label: 'Profile page'
}, {
  value: 'website',
  label: 'Website'
}, {
  value: 'organization',
  label: 'Organization'
}, {
  value: 'event',
  label: 'Event'
}, {
  value: 'recipe',
  label: 'Recipe'
}, {
  value: 'videoobject',
  label: 'Video object'
}, {
  value: 'book',
  label: 'Book'
}, {
  value: 'service',
  label: 'Service'
}, {
  value: 'localbusiness',
  label: 'Local business'
}];

// Social card layout options
const cardLayoutOptions = [{
  value: 'default',
  label: 'Default',
  description: 'Title with accent bar at bottom'
}, {
  value: 'centered',
  label: 'Centered',
  description: 'Centered text layout'
}, {
  value: 'minimal',
  label: 'Minimal',
  description: 'Text only, no accent'
}, {
  value: 'bold',
  label: 'Bold',
  description: 'Large accent block'
}];

// Logo position options
const logoPositionOptions = [{
  value: 'top-left',
  label: 'Top Left'
}, {
  value: 'top-right',
  label: 'Top Right'
}, {
  value: 'bottom-left',
  label: 'Bottom Left'
}, {
  value: 'bottom-right',
  label: 'Bottom Right'
}, {
  value: 'center',
  label: 'Center'
}];
const SearchAppearance = () => {
  const [activeTab, setActiveTab] = (0,_hooks_useUrlTab__WEBPACK_IMPORTED_MODULE_7__["default"])({
    tabs: searchAppearanceTabs,
    defaultTab: 'homepage'
  });

  // Global state
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [saveMessage, setSaveMessage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [siteInfo, setSiteInfo] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});

  // Variables for template rendering
  const [variables, setVariables] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const [variableValues, setVariableValues] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});

  // Homepage state
  const [homepage, setHomepage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    meta_title: '',
    meta_description: '',
    meta_keywords: ''
  });
  const [separator, setSeparator] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('-');
  const [separatorOptions, setSeparatorOptions] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});

  // Post types state
  const [postTypes, setPostTypes] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [editingPostType, setEditingPostType] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [schemaOptions, setSchemaOptions] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    page: {},
    article: {}
  });

  // Taxonomies state
  const [taxonomies, setTaxonomies] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [editingTaxonomy, setEditingTaxonomy] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);

  // Archives state
  const [archives, setArchives] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [editingArchive, setEditingArchive] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);

  // Social Settings state
  const [socialDefaults, setSocialDefaults] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    og_title: '',
    og_description: '',
    twitter_title: '',
    twitter_description: '',
    image_source: '',
    schema_itemtype: ''
  });
  const [postTypeSocialDefaults, setPostTypeSocialDefaults] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const [editingPostTypeSocial, setEditingPostTypeSocial] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);

  // Social Cards state
  const [cardDesign, setCardDesign] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    background_color: '#1a1a36',
    accent_color: '#5a84ff',
    text_color: '#ffffff',
    title_font_size: 48,
    site_font_size: 24,
    logo_url: '',
    logo_position: 'bottom-left',
    layout: 'default'
  });
  const [cardPreviewTitle, setCardPreviewTitle] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('Sample Post Title - Understanding Core Web Vitals');
  const [cardModuleEnabled, setCardModuleEnabled] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);

  // AI Generation modal state
  const [aiModal, setAiModal] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    isOpen: false,
    fieldType: 'title',
    onApply: null,
    context: {}
  });

  // Open AI modal for a specific field
  const openAiModal = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)((fieldType, onApply, context = {}) => {
    setAiModal({
      isOpen: true,
      fieldType,
      onApply,
      context
    });
  }, []);

  // Close AI modal
  const closeAiModal = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(() => {
    setAiModal({
      isOpen: false,
      fieldType: 'title',
      onApply: null,
      context: {}
    });
  }, []);

  // Handle AI generated content
  const handleAiGenerate = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(result => {
    if (aiModal.onApply && result) {
      aiModal.onApply(result);
    }
  }, [aiModal]);

  // Fetch all data on mount
  const fetchData = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setLoading(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/search-appearance'
      });
      if (response.success) {
        const data = response.data;
        setHomepage(data.homepage || {});
        setSeparator(data.separator || '-');
        setSeparatorOptions(data.separator_options || {});
        setPostTypes(data.post_types || []);
        setTaxonomies(data.taxonomies || []);
        setArchives(data.archives || []);
        setSchemaOptions(data.schema_options || {
          page: {},
          article: {}
        });
        setSiteInfo(data.site_info || {});
        setVariables(data.variables || {});
        setVariableValues(data.variable_values || {});
        // Social settings
        setSocialDefaults(data.social_defaults || {
          og_title: '',
          og_description: '',
          twitter_title: '',
          twitter_description: '',
          image_source: '',
          schema_itemtype: ''
        });
        setPostTypeSocialDefaults(data.post_type_social_defaults || {});
        // Social cards
        setCardDesign(data.card_design || {
          background_color: '#1a1a36',
          accent_color: '#5a84ff',
          text_color: '#ffffff',
          title_font_size: 48,
          site_font_size: 24,
          logo_url: '',
          logo_position: 'bottom-left',
          layout: 'default'
        });
        setCardModuleEnabled(data.card_module_enabled !== false);
      }
    } catch (error) {
      console.error('Failed to fetch search appearance settings:', error);
    } finally {
      setLoading(false);
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchData();
  }, [fetchData]);

  // Clear save message after 3 seconds
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (saveMessage) {
      const timer = setTimeout(() => setSaveMessage(''), 3000);
      return () => clearTimeout(timer);
    }
  }, [saveMessage]);

  // Update variable values when separator changes
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setVariableValues(prev => ({
      ...prev,
      separator: separator
    }));
  }, [separator]);

  // Apply modifier to a value (supports: upper, lower, capitalize, etc.)
  const applyModifier = (value, modifier) => {
    if (!value || !modifier) return value;
    const mod = modifier.trim().toLowerCase();
    switch (mod) {
      case 'upper':
      case 'uppercase':
        return String(value).toUpperCase();
      case 'lower':
      case 'lowercase':
        return String(value).toLowerCase();
      case 'capitalize':
      case 'title':
        return String(value).replace(/\b\w/g, c => c.toUpperCase());
      case 'trim':
        return String(value).trim();
      default:
        return value;
    }
  };

  // Generate preview from template using variable values
  const renderTemplatePreview = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)((template, contextOverrides = {}) => {
    if (!template) return '';
    let preview = template;
    const allValues = {
      ...variableValues,
      ...contextOverrides
    };

    // Replace all {{variable}} or {{variable | modifier}} patterns
    preview = preview.replace(/\{\{([^}]+)\}\}/g, (match, content) => {
      const trimmedContent = content.trim();

      // Check for modifier (e.g., "post_title | upper")
      const pipeIndex = trimmedContent.indexOf('|');
      if (pipeIndex > -1) {
        const baseTag = trimmedContent.substring(0, pipeIndex).trim();
        const modifier = trimmedContent.substring(pipeIndex + 1).trim();
        const baseValue = allValues[baseTag];
        if (baseValue !== undefined) {
          return applyModifier(baseValue, modifier);
        }
        return match; // Return original if no value found
      }

      // Simple variable without modifier
      return allValues[trimmedContent] !== undefined ? allValues[trimmedContent] : match;
    });
    return preview;
  }, [variableValues]);

  // Save homepage settings
  const saveHomepage = async () => {
    setSaving(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/search-appearance/homepage',
        method: 'POST',
        data: homepage
      });
      if (response.success) {
        setSaveMessage('Homepage settings saved successfully.');
      }
    } catch (error) {
      console.error('Failed to save homepage settings:', error);
      setSaveMessage('Failed to save settings.');
    } finally {
      setSaving(false);
    }
  };

  // Save separator
  const saveSeparator = async newSeparator => {
    setSeparator(newSeparator);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/search-appearance/separator',
        method: 'POST',
        data: {
          separator: newSeparator
        }
      });
    } catch (error) {
      console.error('Failed to save separator:', error);
    }
  };

  // Save single post type
  const savePostType = async postType => {
    setSaving(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/search-appearance/post-types/${postType.slug}`,
        method: 'POST',
        data: postType
      });
      if (response.success) {
        setPostTypes(prev => prev.map(pt => pt.slug === postType.slug ? {
          ...pt,
          ...postType
        } : pt));
        setEditingPostType(null);
        setSaveMessage('Post type settings saved.');
      }
    } catch (error) {
      console.error('Failed to save post type:', error);
    } finally {
      setSaving(false);
    }
  };

  // Save single taxonomy
  const saveTaxonomy = async taxonomy => {
    setSaving(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/search-appearance/taxonomies/${taxonomy.slug}`,
        method: 'POST',
        data: taxonomy
      });
      if (response.success) {
        setTaxonomies(prev => prev.map(tax => tax.slug === taxonomy.slug ? {
          ...tax,
          ...taxonomy
        } : tax));
        setEditingTaxonomy(null);
        setSaveMessage('Taxonomy settings saved.');
      }
    } catch (error) {
      console.error('Failed to save taxonomy:', error);
    } finally {
      setSaving(false);
    }
  };

  // Save archives
  const saveArchives = async () => {
    setSaving(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/search-appearance/archives',
        method: 'POST',
        data: archives
      });
      if (response.success) {
        setArchives(response.data);
        setEditingArchive(null);
        setSaveMessage('Archive settings saved.');
      }
    } catch (error) {
      console.error('Failed to save archives:', error);
    } finally {
      setSaving(false);
    }
  };

  // Save social defaults
  const saveSocialDefaults = async () => {
    setSaving(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/search-appearance/social-defaults',
        method: 'POST',
        data: socialDefaults
      });
      if (response.success) {
        setSaveMessage('Social settings saved.');
      }
    } catch (error) {
      console.error('Failed to save social defaults:', error);
    } finally {
      setSaving(false);
    }
  };

  // Save post type social settings
  const savePostTypeSocial = async (slug, settings) => {
    setSaving(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: `/wpseopilot/v2/search-appearance/social-defaults/${slug}`,
        method: 'POST',
        data: settings
      });
      if (response.success) {
        setPostTypeSocialDefaults(prev => ({
          ...prev,
          [slug]: settings
        }));
        setEditingPostTypeSocial(null);
        setSaveMessage('Post type social settings saved.');
      }
    } catch (error) {
      console.error('Failed to save post type social settings:', error);
    } finally {
      setSaving(false);
    }
  };

  // Save card design
  const saveCardDesign = async () => {
    setSaving(true);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/search-appearance/card-design',
        method: 'POST',
        data: cardDesign
      });
      if (response.success) {
        setSaveMessage('Social card design saved.');
      }
    } catch (error) {
      console.error('Failed to save card design:', error);
    } finally {
      setSaving(false);
    }
  };
  if (loading) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "loading-state"
    }, "Loading search appearance settings..."));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Search Appearance"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Control how your content appears in search results.")), saveMessage && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "pill success"
  }, saveMessage)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_SubTabs__WEBPACK_IMPORTED_MODULE_3__["default"], {
    tabs: searchAppearanceTabs,
    activeTab: activeTab,
    onChange: setActiveTab,
    ariaLabel: "Search appearance sections"
  }), activeTab === 'homepage' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Homepage SEO"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Configure default title and meta description for your homepage."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_SearchPreview__WEBPACK_IMPORTED_MODULE_4__["default"], {
    title: renderTemplatePreview(homepage.meta_title || `{{site_title}} {{separator}} {{tagline}}`),
    description: renderTemplatePreview(homepage.meta_description || siteInfo.description || ''),
    domain: siteInfo.domain,
    url: siteInfo.url,
    favicon: siteInfo.favicon
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "home-title"
  }, "Homepage Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "The title tag for your homepage.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    id: "home-title",
    value: homepage.meta_title,
    onChange: val => setHomepage({
      ...homepage,
      meta_title: val
    }),
    placeholder: `${siteInfo.name} ${separator} ${siteInfo.description}`,
    variables: variables,
    variableValues: variableValues,
    context: "global",
    maxLength: 60,
    onAiClick: () => openAiModal('title', val => setHomepage({
      ...homepage,
      meta_title: val
    }), {
      type: 'Homepage'
    }),
    aiEnabled: aiEnabled
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "home-desc"
  }, "Meta Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "A brief description of your website (150-160 chars recommended).")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    id: "home-desc",
    value: homepage.meta_description,
    onChange: val => setHomepage({
      ...homepage,
      meta_description: val
    }),
    placeholder: "A brief description of your website...",
    variables: variables,
    variableValues: variableValues,
    context: "global",
    multiline: true,
    maxLength: 160,
    onAiClick: () => openAiModal('description', val => setHomepage({
      ...homepage,
      meta_description: val
    }), {
      type: 'Homepage'
    }),
    aiEnabled: aiEnabled
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Title Separator"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Character used between title parts across your site.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "separator-selector"
  }, Object.entries(separatorOptions).map(([value, label]) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: value,
    type: "button",
    className: `separator-option ${separator === value ? 'active' : ''}`,
    onClick: () => saveSeparator(value),
    title: label
  }, value)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "separator-custom"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    className: "separator-custom__input",
    value: !Object.keys(separatorOptions).includes(separator) ? separator : '',
    onChange: e => saveSeparator(e.target.value),
    placeholder: "Custom",
    maxLength: 5
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "home-keywords"
  }, "Meta Keywords"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Comma-separated keywords (optional, less relevant for modern SEO).")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    id: "home-keywords",
    className: "input",
    value: homepage.meta_keywords || '',
    onChange: e => setHomepage({
      ...homepage,
      meta_keywords: e.target.value
    }),
    placeholder: "keyword1, keyword2, keyword3"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: saveHomepage,
    disabled: saving
  }, saving ? 'Saving...' : 'Save Homepage Settings')))), activeTab === 'content-types' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Content Types"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Configure SEO defaults for each post type."))), editingPostType ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(PostTypeEditor, {
    postType: editingPostType,
    schemaOptions: schemaOptions,
    separator: separator,
    siteInfo: siteInfo,
    variables: variables,
    variableValues: variableValues,
    onSave: savePostType,
    onCancel: () => setEditingPostType(null),
    saving: saving,
    renderTemplatePreview: renderTemplatePreview,
    openAiModal: openAiModal
  }) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "data-table"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Post Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Title Preview"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Show in Search"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Posts"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Action"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, postTypes.map(pt => {
    const template = pt.title_template || '{{post_title}} {{separator}} {{site_title}}';
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
      key: pt.slug
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, pt.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted",
      style: {
        display: 'block',
        fontSize: '12px'
      }
    }, pt.slug)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "title-preview-cell"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "title-preview-cell__title"
    }, renderTemplatePreview(template)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", {
      className: "title-preview-cell__template"
    }, template))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: `pill ${pt.noindex ? 'warning' : 'success'}`
    }, pt.noindex ? 'No' : 'Yes')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, pt.count), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "link-button",
      onClick: () => setEditingPostType({
        ...pt
      })
    }, "Edit")));
  })))), activeTab === 'taxonomies' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Taxonomies"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Configure SEO settings for categories, tags, and custom taxonomies."))), editingTaxonomy ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(TaxonomyEditor, {
    taxonomy: editingTaxonomy,
    separator: separator,
    siteInfo: siteInfo,
    variables: variables,
    variableValues: variableValues,
    onSave: saveTaxonomy,
    onCancel: () => setEditingTaxonomy(null),
    saving: saving,
    renderTemplatePreview: renderTemplatePreview,
    openAiModal: openAiModal
  }) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "data-table"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Taxonomy"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Title Preview"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Show in Search"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Terms"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Action"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, taxonomies.map(tax => {
    const template = tax.title_template || '{{term_title}} {{separator}} {{site_title}}';
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
      key: tax.slug
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, tax.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted",
      style: {
        display: 'block',
        fontSize: '12px'
      }
    }, tax.slug)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "title-preview-cell"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "title-preview-cell__title"
    }, renderTemplatePreview(template)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", {
      className: "title-preview-cell__template"
    }, template))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: `pill ${tax.noindex ? 'warning' : 'success'}`
    }, tax.noindex ? 'No' : 'Yes')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, tax.count), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "link-button",
      onClick: () => setEditingTaxonomy({
        ...tax
      })
    }, "Edit")));
  })))), activeTab === 'archives' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Archives"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Configure SEO for author, date, search, and 404 pages."))), editingArchive ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(ArchiveEditor, {
    archive: editingArchive,
    separator: separator,
    siteInfo: siteInfo,
    variables: variables,
    variableValues: variableValues,
    onSave: updated => {
      setArchives(prev => prev.map(a => a.slug === updated.slug ? updated : a));
      setEditingArchive(null);
    },
    onCancel: () => setEditingArchive(null),
    renderTemplatePreview: renderTemplatePreview,
    openAiModal: openAiModal
  }) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "data-table"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Archive Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Title Preview"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Show in Search"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Action"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, archives.map(archive => {
    const template = archive.title_template || '{{archive_title}} {{separator}} {{site_title}}';
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
      key: archive.slug
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, archive.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted",
      style: {
        display: 'block',
        fontSize: '12px'
      }
    }, archive.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "title-preview-cell"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "title-preview-cell__title"
    }, renderTemplatePreview(template)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", {
      className: "title-preview-cell__template"
    }, template))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: `pill ${archive.noindex ? 'warning' : 'success'}`
    }, archive.noindex ? 'No' : 'Yes')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "link-button",
      onClick: () => setEditingArchive({
        ...archive
      })
    }, "Edit")));
  })))), activeTab === 'social-settings' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Social Settings"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Configure default Open Graph, Twitter, and schema values for social sharing."))), editingPostTypeSocial ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "type-editor__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Edit ", editingPostTypeSocial.name, " Social Settings"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "link-button",
    onClick: () => setEditingPostTypeSocial(null)
  }, "Cancel")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "OG Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Open Graph title for Facebook shares.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: editingPostTypeSocial.og_title || '',
    onChange: val => setEditingPostTypeSocial({
      ...editingPostTypeSocial,
      og_title: val
    }),
    placeholder: "{{post_title}} {{separator}} {{site_title}}",
    variables: variables,
    variableValues: variableValues,
    context: "post",
    maxLength: 60,
    onAiClick: () => openAiModal('title', val => setEditingPostTypeSocial({
      ...editingPostTypeSocial,
      og_title: val
    }), {
      type: editingPostTypeSocial.name,
      name: 'OG Title'
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "OG Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Open Graph description for Facebook shares.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: editingPostTypeSocial.og_description || '',
    onChange: val => setEditingPostTypeSocial({
      ...editingPostTypeSocial,
      og_description: val
    }),
    placeholder: "{{post_excerpt}}",
    variables: variables,
    variableValues: variableValues,
    context: "post",
    multiline: true,
    maxLength: 160,
    onAiClick: () => openAiModal('description', val => setEditingPostTypeSocial({
      ...editingPostTypeSocial,
      og_description: val
    }), {
      type: editingPostTypeSocial.name,
      name: 'OG Description'
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Twitter Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Twitter card title.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: editingPostTypeSocial.twitter_title || '',
    onChange: val => setEditingPostTypeSocial({
      ...editingPostTypeSocial,
      twitter_title: val
    }),
    placeholder: "{{post_title}} {{separator}} {{site_title}}",
    variables: variables,
    variableValues: variableValues,
    context: "post",
    maxLength: 60,
    onAiClick: () => openAiModal('title', val => setEditingPostTypeSocial({
      ...editingPostTypeSocial,
      twitter_title: val
    }), {
      type: editingPostTypeSocial.name,
      name: 'Twitter Title'
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Twitter Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Twitter card description.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: editingPostTypeSocial.twitter_description || '',
    onChange: val => setEditingPostTypeSocial({
      ...editingPostTypeSocial,
      twitter_description: val
    }),
    placeholder: "{{post_excerpt}}",
    variables: variables,
    variableValues: variableValues,
    context: "post",
    multiline: true,
    maxLength: 160,
    onAiClick: () => openAiModal('description', val => setEditingPostTypeSocial({
      ...editingPostTypeSocial,
      twitter_description: val
    }), {
      type: editingPostTypeSocial.name,
      name: 'Twitter Description'
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Image URL"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Fallback image for social sharing.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "url",
    className: "input",
    value: editingPostTypeSocial.image_source || '',
    onChange: e => setEditingPostTypeSocial({
      ...editingPostTypeSocial,
      image_source: e.target.value
    }),
    placeholder: "https://example.com/image.jpg"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Schema Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Schema.org type for this post type.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    className: "input",
    value: editingPostTypeSocial.schema_itemtype || '',
    onChange: e => setEditingPostTypeSocial({
      ...editingPostTypeSocial,
      schema_itemtype: e.target.value
    })
  }, schemaTypeOptions.map(opt => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: opt.value,
    value: opt.value
  }, opt.label))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: () => savePostTypeSocial(editingPostTypeSocial.slug, editingPostTypeSocial),
    disabled: saving
  }, saving ? 'Saving...' : 'Save Settings'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button",
    onClick: () => setEditingPostTypeSocial(null)
  }, "Cancel"))) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-form",
    style: {
      marginBottom: '32px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", {
    style: {
      marginBottom: '16px'
    }
  }, "Global Social Defaults"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted",
    style: {
      marginBottom: '24px'
    }
  }, "These defaults apply when posts don't have custom social values."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "OG Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Default Open Graph title for Facebook.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: socialDefaults.og_title || '',
    onChange: val => setSocialDefaults({
      ...socialDefaults,
      og_title: val
    }),
    placeholder: "{{site_title}} {{separator}} {{tagline}}",
    variables: variables,
    variableValues: variableValues,
    context: "global",
    maxLength: 60,
    onAiClick: () => openAiModal('title', val => setSocialDefaults({
      ...socialDefaults,
      og_title: val
    }), {
      type: 'Social',
      name: 'Open Graph Title'
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "OG Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Default Open Graph description.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: socialDefaults.og_description || '',
    onChange: val => setSocialDefaults({
      ...socialDefaults,
      og_description: val
    }),
    placeholder: "{{tagline}}",
    variables: variables,
    variableValues: variableValues,
    context: "global",
    multiline: true,
    maxLength: 160,
    onAiClick: () => openAiModal('description', val => setSocialDefaults({
      ...socialDefaults,
      og_description: val
    }), {
      type: 'Social',
      name: 'Open Graph Description'
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Twitter Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Default Twitter card title.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: socialDefaults.twitter_title || '',
    onChange: val => setSocialDefaults({
      ...socialDefaults,
      twitter_title: val
    }),
    placeholder: "{{site_title}} {{separator}} {{tagline}}",
    variables: variables,
    variableValues: variableValues,
    context: "global",
    maxLength: 60,
    onAiClick: () => openAiModal('title', val => setSocialDefaults({
      ...socialDefaults,
      twitter_title: val
    }), {
      type: 'Social',
      name: 'Twitter Card Title'
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Twitter Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Default Twitter card description.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: socialDefaults.twitter_description || '',
    onChange: val => setSocialDefaults({
      ...socialDefaults,
      twitter_description: val
    }),
    placeholder: "{{tagline}}",
    variables: variables,
    variableValues: variableValues,
    context: "global",
    multiline: true,
    maxLength: 160,
    onAiClick: () => openAiModal('description', val => setSocialDefaults({
      ...socialDefaults,
      twitter_description: val
    }), {
      type: 'Social',
      name: 'Twitter Card Description'
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Fallback Image URL"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Used when posts don't have a featured image (1200x630px recommended).")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "url",
    className: "input",
    value: socialDefaults.image_source || '',
    onChange: e => setSocialDefaults({
      ...socialDefaults,
      image_source: e.target.value
    }),
    placeholder: "https://example.com/default-social.jpg"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Default Schema Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Controls the og:type meta tag for content.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    className: "input",
    value: socialDefaults.schema_itemtype || '',
    onChange: e => setSocialDefaults({
      ...socialDefaults,
      schema_itemtype: e.target.value
    })
  }, schemaTypeOptions.map(opt => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: opt.value,
    value: opt.value
  }, opt.label))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: saveSocialDefaults,
    disabled: saving
  }, saving ? 'Saving...' : 'Save Global Defaults'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      marginTop: '32px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", {
    style: {
      marginBottom: '8px'
    }
  }, "Post Type Specific Settings"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted",
    style: {
      marginBottom: '16px'
    }
  }, "Override default social settings for specific post types."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    className: "data-table"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("thead", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Post Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "OG Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Schema Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("th", null, "Action"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, postTypes.map(pt => {
    const socialSettings = postTypeSocialDefaults[pt.slug] || {};
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", {
      key: pt.slug
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, pt.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted",
      style: {
        display: 'block',
        fontSize: '12px'
      }
    }, pt.slug)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted"
    }, socialSettings.og_title || 'Using global default')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted"
    }, socialSettings.schema_itemtype || 'Article')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "link-button",
      onClick: () => setEditingPostTypeSocial({
        slug: pt.slug,
        name: pt.name,
        ...socialSettings
      })
    }, "Edit")));
  })))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_AiGenerateModal__WEBPACK_IMPORTED_MODULE_6__["default"], {
    isOpen: aiModal.isOpen,
    onClose: closeAiModal,
    onGenerate: handleAiGenerate,
    fieldType: aiModal.fieldType,
    variableValues: variableValues,
    context: aiModal.context
  }), activeTab === 'social-cards' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "table-toolbar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Social Cards"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Customize the appearance of dynamically generated social card images.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `pill ${cardModuleEnabled ? 'success' : 'warning'}`
  }, cardModuleEnabled ? 'Active' : 'Disabled'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      marginBottom: '32px',
      padding: '24px',
      background: '#f8f9fa',
      borderRadius: '8px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", {
    style: {
      marginBottom: '16px'
    }
  }, "Live Preview"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      display: 'flex',
      gap: '16px',
      marginBottom: '16px',
      alignItems: 'flex-end'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      flex: 1
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    style: {
      display: 'block',
      marginBottom: '8px',
      fontSize: '14px',
      fontWeight: 500
    }
  }, "Sample Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    className: "input",
    value: cardPreviewTitle,
    onChange: e => setCardPreviewTitle(e.target.value)
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-card-preview",
    style: {
      width: '100%',
      maxWidth: '600px',
      aspectRatio: '1200/630',
      background: cardDesign.background_color,
      borderRadius: '8px',
      display: 'flex',
      flexDirection: 'column',
      justifyContent: cardDesign.layout === 'centered' ? 'center' : 'flex-end',
      alignItems: cardDesign.layout === 'centered' ? 'center' : 'flex-start',
      padding: '32px',
      position: 'relative',
      overflow: 'hidden'
    }
  }, cardDesign.layout !== 'minimal' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      position: 'absolute',
      bottom: 0,
      left: 0,
      right: 0,
      height: cardDesign.layout === 'bold' ? '30%' : '6px',
      background: cardDesign.accent_color
    }
  }), cardDesign.logo_url && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: cardDesign.logo_url,
    alt: "Logo",
    style: {
      position: 'absolute',
      width: '48px',
      height: '48px',
      objectFit: 'contain',
      ...(cardDesign.logo_position === 'top-left' && {
        top: '24px',
        left: '24px'
      }),
      ...(cardDesign.logo_position === 'top-right' && {
        top: '24px',
        right: '24px'
      }),
      ...(cardDesign.logo_position === 'bottom-left' && {
        bottom: '24px',
        left: '24px'
      }),
      ...(cardDesign.logo_position === 'bottom-right' && {
        bottom: '24px',
        right: '24px'
      }),
      ...(cardDesign.logo_position === 'center' && {
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)'
      })
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", {
    style: {
      color: cardDesign.text_color,
      fontSize: `${Math.max(16, cardDesign.title_font_size / 3)}px`,
      fontWeight: 700,
      margin: 0,
      marginBottom: '8px',
      textAlign: cardDesign.layout === 'centered' ? 'center' : 'left',
      zIndex: 1
    }
  }, cardPreviewTitle), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    style: {
      color: cardDesign.text_color,
      fontSize: `${Math.max(10, cardDesign.site_font_size / 3)}px`,
      opacity: 0.8,
      zIndex: 1
    }
  }, siteInfo.name || 'Your Site Name'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Layout Style"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Choose the overall layout for social cards.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      display: 'flex',
      gap: '12px',
      flexWrap: 'wrap'
    }
  }, cardLayoutOptions.map(layout => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    key: layout.value,
    style: {
      display: 'flex',
      flexDirection: 'column',
      padding: '12px 16px',
      border: `2px solid ${cardDesign.layout === layout.value ? '#2271b1' : '#ddd'}`,
      borderRadius: '8px',
      cursor: 'pointer',
      background: cardDesign.layout === layout.value ? '#f0f6fc' : '#fff',
      minWidth: '120px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "card-layout",
    value: layout.value,
    checked: cardDesign.layout === layout.value,
    onChange: e => setCardDesign({
      ...cardDesign,
      layout: e.target.value
    }),
    style: {
      display: 'none'
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", {
    style: {
      fontSize: '14px'
    }
  }, layout.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    style: {
      fontSize: '12px',
      color: '#666'
    }
  }, layout.description)))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Background Color")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      display: 'flex',
      gap: '8px',
      alignItems: 'center'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "color",
    value: cardDesign.background_color,
    onChange: e => setCardDesign({
      ...cardDesign,
      background_color: e.target.value
    }),
    style: {
      width: '48px',
      height: '36px',
      border: '1px solid #ddd',
      borderRadius: '4px',
      cursor: 'pointer'
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    className: "input",
    value: cardDesign.background_color,
    onChange: e => setCardDesign({
      ...cardDesign,
      background_color: e.target.value
    }),
    style: {
      width: '100px'
    }
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Accent Color")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      display: 'flex',
      gap: '8px',
      alignItems: 'center'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "color",
    value: cardDesign.accent_color,
    onChange: e => setCardDesign({
      ...cardDesign,
      accent_color: e.target.value
    }),
    style: {
      width: '48px',
      height: '36px',
      border: '1px solid #ddd',
      borderRadius: '4px',
      cursor: 'pointer'
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    className: "input",
    value: cardDesign.accent_color,
    onChange: e => setCardDesign({
      ...cardDesign,
      accent_color: e.target.value
    }),
    style: {
      width: '100px'
    }
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Text Color")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      display: 'flex',
      gap: '8px',
      alignItems: 'center'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "color",
    value: cardDesign.text_color,
    onChange: e => setCardDesign({
      ...cardDesign,
      text_color: e.target.value
    }),
    style: {
      width: '48px',
      height: '36px',
      border: '1px solid #ddd',
      borderRadius: '4px',
      cursor: 'pointer'
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    className: "input",
    value: cardDesign.text_color,
    onChange: e => setCardDesign({
      ...cardDesign,
      text_color: e.target.value
    }),
    style: {
      width: '100px'
    }
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Title Font Size (px)")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    className: "input",
    value: cardDesign.title_font_size,
    onChange: e => setCardDesign({
      ...cardDesign,
      title_font_size: parseInt(e.target.value) || 48
    }),
    min: 24,
    max: 96,
    style: {
      width: '100px'
    }
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Site Name Font Size (px)")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    className: "input",
    value: cardDesign.site_font_size,
    onChange: e => setCardDesign({
      ...cardDesign,
      site_font_size: parseInt(e.target.value) || 24
    }),
    min: 12,
    max: 48,
    style: {
      width: '100px'
    }
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Logo URL"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Upload a logo to display on social cards (200x200px recommended).")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "url",
    className: "input",
    value: cardDesign.logo_url,
    onChange: e => setCardDesign({
      ...cardDesign,
      logo_url: e.target.value
    }),
    placeholder: "https://example.com/logo.png"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Logo Position")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    className: "input",
    value: cardDesign.logo_position,
    onChange: e => setCardDesign({
      ...cardDesign,
      logo_position: e.target.value
    })
  }, logoPositionOptions.map(opt => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: opt.value,
    value: opt.value
  }, opt.label))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: saveCardDesign,
    disabled: saving
  }, saving ? 'Saving...' : 'Save Card Design')))));
};

/**
 * Post Type Editor Component
 */
const PostTypeEditor = ({
  postType,
  schemaOptions,
  separator,
  siteInfo,
  variables,
  variableValues,
  onSave,
  onCancel,
  saving,
  renderTemplatePreview,
  openAiModal
}) => {
  const [data, setData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(postType);
  const previewTitle = renderTemplatePreview(data.title_template || '{{post_title}} {{separator}} {{site_title}}');
  const previewDescription = renderTemplatePreview(data.description_template || '{{post_excerpt}}');
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "type-editor"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "type-editor__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Edit: ", postType.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "link-button",
    onClick: onCancel
  }, "Cancel")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_SearchPreview__WEBPACK_IMPORTED_MODULE_4__["default"], {
    title: previewTitle,
    description: previewDescription,
    domain: siteInfo.domain,
    favicon: siteInfo.favicon
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Show in Search Results"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Allow search engines to index this content type.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: !data.noindex,
    onChange: e => setData({
      ...data,
      noindex: !e.target.checked
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-text"
  }, data.noindex ? 'Hidden' : 'Visible')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Title Template"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Click \"Variables\" to insert dynamic content.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: data.title_template,
    onChange: val => setData({
      ...data,
      title_template: val
    }),
    placeholder: "{{post_title}} {{separator}} {{site_title}}",
    variables: variables,
    variableValues: variableValues,
    context: "post",
    maxLength: 60,
    onAiClick: () => openAiModal('title', val => setData({
      ...data,
      title_template: val
    }), {
      type: 'Post Type',
      name: postType.name
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Description Template"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Default meta description for this post type.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: data.description_template,
    onChange: val => setData({
      ...data,
      description_template: val
    }),
    placeholder: "{{post_excerpt}}",
    variables: variables,
    variableValues: variableValues,
    context: "post",
    multiline: true,
    maxLength: 160,
    onAiClick: () => openAiModal('description', val => setData({
      ...data,
      description_template: val
    }), {
      type: 'Post Type',
      name: postType.name
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Schema Page Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Default structured data page type.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: data.schema_page,
    onChange: e => setData({
      ...data,
      schema_page: e.target.value
    })
  }, Object.entries(schemaOptions.page).map(([value, label]) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: value,
    value: value
  }, label))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Schema Article Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Default structured data article type.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: data.schema_article,
    onChange: e => setData({
      ...data,
      schema_article: e.target.value
    })
  }, Object.entries(schemaOptions.article).map(([value, label]) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: value,
    value: value
  }, label))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Show SEO Controls"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Show SEO meta box in editor for this post type.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: data.show_seo_controls,
    onChange: e => setData({
      ...data,
      show_seo_controls: e.target.checked
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-text"
  }, data.show_seo_controls ? 'Enabled' : 'Disabled')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: () => onSave(data),
    disabled: saving
  }, saving ? 'Saving...' : 'Save Changes'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: onCancel
  }, "Cancel"))));
};

/**
 * Taxonomy Editor Component
 */
const TaxonomyEditor = ({
  taxonomy,
  separator,
  siteInfo,
  variables,
  variableValues,
  onSave,
  onCancel,
  saving,
  renderTemplatePreview,
  openAiModal
}) => {
  const [data, setData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(taxonomy);
  const previewTitle = renderTemplatePreview(data.title_template || '{{term_title}} Archives {{separator}} {{site_title}}');
  const previewDescription = renderTemplatePreview(data.description_template || '{{term_description}}');
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "type-editor"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "type-editor__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Edit: ", taxonomy.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "link-button",
    onClick: onCancel
  }, "Cancel")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_SearchPreview__WEBPACK_IMPORTED_MODULE_4__["default"], {
    title: previewTitle,
    description: previewDescription,
    domain: siteInfo.domain,
    favicon: siteInfo.favicon
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Show in Search Results"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Allow search engines to index this taxonomy.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: !data.noindex,
    onChange: e => setData({
      ...data,
      noindex: !e.target.checked
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-text"
  }, data.noindex ? 'Hidden' : 'Visible')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Title Template"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Click \"Variables\" to insert dynamic content.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: data.title_template,
    onChange: val => setData({
      ...data,
      title_template: val
    }),
    placeholder: "{{term_title}} Archives {{separator}} {{site_title}}",
    variables: variables,
    variableValues: variableValues,
    context: "taxonomy",
    maxLength: 60,
    onAiClick: () => openAiModal('title', val => setData({
      ...data,
      title_template: val
    }), {
      type: 'Taxonomy',
      name: taxonomy.name
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Description Template"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Default meta description for taxonomy archives.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: data.description_template,
    onChange: val => setData({
      ...data,
      description_template: val
    }),
    placeholder: "{{term_description}}",
    variables: variables,
    variableValues: variableValues,
    context: "taxonomy",
    multiline: true,
    maxLength: 160,
    onAiClick: () => openAiModal('description', val => setData({
      ...data,
      description_template: val
    }), {
      type: 'Taxonomy',
      name: taxonomy.name
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Show SEO Controls"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Show SEO fields when editing terms.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: data.show_seo_controls,
    onChange: e => setData({
      ...data,
      show_seo_controls: e.target.checked
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-text"
  }, data.show_seo_controls ? 'Enabled' : 'Disabled')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: () => onSave(data),
    disabled: saving
  }, saving ? 'Saving...' : 'Save Changes'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: onCancel
  }, "Cancel"))));
};

/**
 * Archive Editor Component
 */
const ArchiveEditor = ({
  archive,
  separator,
  siteInfo,
  variables,
  variableValues,
  onSave,
  onCancel,
  renderTemplatePreview,
  openAiModal
}) => {
  const [data, setData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(archive);

  // Get context for this archive type
  const getArchiveContext = () => {
    switch (archive.slug) {
      case 'author':
        return 'author';
      case 'date':
        return 'archive';
      case 'search':
        return 'archive';
      case '404':
        return 'archive';
      default:
        return 'global';
    }
  };
  const previewTitle = renderTemplatePreview(data.title_template);
  const previewDescription = renderTemplatePreview(data.description_template);
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "type-editor"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "type-editor__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Edit: ", archive.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "link-button",
    onClick: onCancel
  }, "Cancel")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_SearchPreview__WEBPACK_IMPORTED_MODULE_4__["default"], {
    title: previewTitle,
    description: previewDescription,
    domain: siteInfo.domain,
    favicon: siteInfo.favicon
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Show in Search Results"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Allow search engines to index this page type.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: !data.noindex,
    onChange: e => setData({
      ...data,
      noindex: !e.target.checked
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-text"
  }, data.noindex ? 'Hidden' : 'Visible')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Title Template"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Click \"Variables\" to insert dynamic content.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: data.title_template,
    onChange: val => setData({
      ...data,
      title_template: val
    }),
    variables: variables,
    variableValues: variableValues,
    context: getArchiveContext(),
    maxLength: 60,
    onAiClick: () => openAiModal('title', val => setData({
      ...data,
      title_template: val
    }), {
      type: 'Archive',
      name: archive.name
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Description Template")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_TemplateInput__WEBPACK_IMPORTED_MODULE_5__["default"], {
    value: data.description_template,
    onChange: val => setData({
      ...data,
      description_template: val
    }),
    variables: variables,
    variableValues: variableValues,
    context: getArchiveContext(),
    multiline: true,
    maxLength: 160,
    onAiClick: () => openAiModal('description', val => setData({
      ...data,
      description_template: val
    }), {
      type: 'Archive',
      name: archive.name
    })
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: () => onSave(data)
  }, "Save Changes"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: onCancel
  }, "Cancel"))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SearchAppearance);

/***/ },

/***/ "./src-v2/pages/Settings.js"
/*!**********************************!*\
  !*** ./src-v2/pages/Settings.js ***!
  \**********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _components_SubTabs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/SubTabs */ "./src-v2/components/SubTabs.js");
/* harmony import */ var _components_AnalyticsNotice__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/AnalyticsNotice */ "./src-v2/components/AnalyticsNotice.js");
/* harmony import */ var _hooks_useUrlTab__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../hooks/useUrlTab */ "./src-v2/hooks/useUrlTab.js");
/* harmony import */ var _utils_analytics__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/analytics */ "./src-v2/utils/analytics.js");







const settingsTabs = [{
  id: 'general',
  label: 'General'
}, {
  id: 'modules',
  label: 'Modules'
}, {
  id: 'social',
  label: 'Social'
}, {
  id: 'advanced',
  label: 'Advanced'
}, {
  id: 'tools',
  label: 'Tools'
}];
const defaultSettings = {
  // General
  separator: '-',
  knowledge_graph_type: 'organization',
  organization_name: '',
  organization_logo: '',
  person_name: '',
  // Webmaster tools
  google_verification: '',
  bing_verification: '',
  pinterest_verification: '',
  yandex_verification: '',
  baidu_verification: '',
  // Modules
  module_sitemap: true,
  module_redirects: true,
  module_404_log: true,
  module_social_cards: true,
  module_llm_txt: false,
  module_local_seo: false,
  module_internal_linking: true,
  module_schema: true,
  module_breadcrumbs: false,
  module_analytics: false,
  module_search_console: false,
  module_ai_assistant: true,
  // Social
  default_og_image: '',
  twitter_card_type: 'summary_large_image',
  twitter_username: '',
  facebook_app_id: '',
  facebook_admin_id: '',
  // Advanced
  output_clean_head: true,
  remove_shortlinks: true,
  remove_rsd_link: true,
  remove_wlwmanifest: true,
  remove_wp_generator: true,
  remove_feed_links: false,
  disable_json_ld: false,
  disable_emoji: false,
  disable_comments_css: false,
  disable_gutenberg_css: false,
  enable_link_suggestions: true,
  enable_internal_link_count: true,
  enable_cornerstone_content: true,
  cache_schema: true,
  purge_on_save: true,
  enable_rest_api: true,
  debug_mode: false,
  // Performance
  lazy_load_schema: true,
  minify_schema_output: true,
  async_schema_validation: false,
  // API Keys
  openai_api_key: '',
  google_api_key: '',
  bing_api_key: ''
};
const Settings = () => {
  const [activeTab, setActiveTab] = (0,_hooks_useUrlTab__WEBPACK_IMPORTED_MODULE_5__["default"])({
    tabs: settingsTabs,
    defaultTab: 'general'
  });
  const [settings, setSettings] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(defaultSettings);
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [saved, setSaved] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [importFile, setImportFile] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [resettingWizard, setResettingWizard] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);

  // Fetch settings
  const fetchSettings = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setLoading(true);
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/settings'
      });
      if (res.success && res.data) {
        setSettings(prev => ({
          ...prev,
          ...res.data
        }));
      }
    } catch (error) {
      console.error('Failed to fetch settings:', error);
    } finally {
      setLoading(false);
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchSettings();
  }, [fetchSettings]);

  // Update setting
  const updateSetting = (key, value) => {
    setSettings(prev => ({
      ...prev,
      [key]: value
    }));
    setSaved(false);
  };

  // Save settings
  const handleSave = async () => {
    setSaving(true);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/settings',
        method: 'POST',
        data: settings
      });
      setSaved(true);
      setTimeout(() => setSaved(false), 3000);
    } catch (error) {
      console.error('Failed to save settings:', error);
    } finally {
      setSaving(false);
    }
  };

  // Export settings
  const handleExport = () => {
    const data = JSON.stringify(settings, null, 2);
    const blob = new Blob([data], {
      type: 'application/json'
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `wpseopilot-settings-${new Date().toISOString().split('T')[0]}.json`;
    a.click();
    URL.revokeObjectURL(url);
  };

  // Import settings
  const handleImport = () => {
    if (!importFile) return;
    const reader = new FileReader();
    reader.onload = e => {
      try {
        const imported = JSON.parse(e.target.result);
        setSettings(prev => ({
          ...prev,
          ...imported
        }));
        setSaved(false);
        setImportFile(null);
      } catch (error) {
        alert('Invalid JSON file');
      }
    };
    reader.readAsText(importFile);
  };

  // Reset to defaults
  const handleReset = () => {
    if (window.confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
      setSettings(defaultSettings);
      setSaved(false);
    }
  };

  // Reset setup wizard
  const handleResetWizard = async () => {
    setResettingWizard(true);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/setup/reset',
        method: 'POST'
      });
      alert('Setup wizard has been reset. It will appear on the next page load.');
    } catch (error) {
      console.error('Failed to reset wizard:', error);
      alert('Failed to reset the setup wizard.');
    } finally {
      setResettingWizard(false);
    }
  };
  if (loading) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "loading-state"
    }, "Loading settings..."));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Settings"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Configure WP SEO Pilot features, integrations, and preferences.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header__actions"
  }, saved && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "save-indicator"
  }, "Saved"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleSave,
    disabled: saving
  }, saving ? 'Saving...' : 'Save Changes'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_SubTabs__WEBPACK_IMPORTED_MODULE_3__["default"], {
    tabs: settingsTabs,
    activeTab: activeTab,
    onChange: setActiveTab,
    ariaLabel: "Settings sections"
  }), activeTab === 'general' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(GeneralTab, {
    settings: settings,
    updateSetting: updateSetting
  }), activeTab === 'modules' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(ModulesTab, {
    settings: settings,
    updateSetting: updateSetting
  }), activeTab === 'social' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(SocialTab, {
    settings: settings,
    updateSetting: updateSetting
  }), activeTab === 'advanced' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(AdvancedTab, {
    settings: settings,
    updateSetting: updateSetting
  }), activeTab === 'tools' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(ToolsTab, {
    settings: settings,
    onExport: handleExport,
    onImport: handleImport,
    onReset: handleReset,
    onResetWizard: handleResetWizard,
    resettingWizard: resettingWizard,
    importFile: importFile,
    setImportFile: setImportFile
  }));
};

// General Tab
const GeneralTab = ({
  settings,
  updateSetting
}) => {
  const separators = [{
    value: '-',
    label: 'Dash (-)'
  }, {
    value: '|',
    label: 'Pipe (|)'
  }, {
    value: '>',
    label: 'Greater than (>)'
  }, {
    value: '<',
    label: 'Less than (<)'
  }, {
    value: '~',
    label: 'Tilde (~)'
  }, {
    value: '•',
    label: 'Bullet (•)'
  }, {
    value: '—',
    label: 'Em dash (—)'
  }];
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-layout"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-main"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Title Separator"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Character used between title parts across your site (e.g., \"Page Title | Site Name\")."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Separator"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Click to select your preferred separator.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "separator-picker"
  }, separators.map(sep => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: sep.value,
    type: "button",
    className: `separator-option ${settings.separator === sep.value ? 'active' : ''}`,
    onClick: () => updateSetting('separator', sep.value),
    title: sep.label
  }, sep.value))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-info-box"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Site Name & Tagline"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "These are managed in WordPress Settings. ", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "options-general.php"
  }, "Edit in Settings \u2192 General")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Knowledge Graph"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Tell search engines who you are with structured data."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Site Represents"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Is this site for a person or organization?")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "radio-group"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "radio-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "kg_type",
    checked: settings.knowledge_graph_type === 'organization',
    onChange: () => updateSetting('knowledge_graph_type', 'organization')
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Organization")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "radio-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "kg_type",
    checked: settings.knowledge_graph_type === 'person',
    onChange: () => updateSetting('knowledge_graph_type', 'person')
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Person"))))), settings.knowledge_graph_type === 'organization' ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "org-name"
  }, "Organization Name")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "org-name",
    type: "text",
    value: settings.organization_name,
    onChange: e => updateSetting('organization_name', e.target.value),
    placeholder: "Acme Corporation"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "org-logo"
  }, "Logo URL"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Recommended: 112x112px minimum.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "org-logo",
    type: "url",
    value: settings.organization_logo,
    onChange: e => updateSetting('organization_logo', e.target.value),
    placeholder: "https://example.com/logo.png"
  })))) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "person-name"
  }, "Person Name")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "person-name",
    type: "text",
    value: settings.person_name,
    onChange: e => updateSetting('person_name', e.target.value),
    placeholder: "John Doe"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Webmaster Tools Verification"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Verify your site with search engines and services."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "google-verify"
  }, "Google Search Console"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Meta tag content value.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "google-verify",
    type: "text",
    value: settings.google_verification,
    onChange: e => updateSetting('google_verification', e.target.value),
    placeholder: "abc123..."
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "bing-verify"
  }, "Bing Webmaster Tools")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "bing-verify",
    type: "text",
    value: settings.bing_verification,
    onChange: e => updateSetting('bing_verification', e.target.value),
    placeholder: "abc123..."
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "pinterest-verify"
  }, "Pinterest")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "pinterest-verify",
    type: "text",
    value: settings.pinterest_verification,
    onChange: e => updateSetting('pinterest_verification', e.target.value),
    placeholder: "abc123..."
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "yandex-verify"
  }, "Yandex")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "yandex-verify",
    type: "text",
    value: settings.yandex_verification,
    onChange: e => updateSetting('yandex_verification', e.target.value),
    placeholder: "abc123..."
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("aside", {
    className: "settings-sidebar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card highlight"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Title Preview"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "title-preview"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "title-preview__text"
  }, "Page Title ", settings.separator, " Site Name")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted",
    style: {
      marginTop: '8px',
      fontSize: '12px'
    }
  }, "This is how titles will be structured across your site."))));
};

// Modules Tab
const ModulesTab = ({
  settings,
  updateSetting
}) => {
  const modules = [{
    key: 'module_sitemap',
    name: 'XML Sitemap',
    desc: 'Generate and manage XML sitemaps for search engines.',
    icon: '🗺️'
  }, {
    key: 'module_redirects',
    name: 'Redirects',
    desc: 'Create and manage URL redirects (301, 302, 307).',
    icon: '↪️'
  }, {
    key: 'module_404_log',
    name: '404 Error Log',
    desc: 'Track and monitor 404 errors on your site.',
    icon: '🚫'
  }, {
    key: 'module_internal_linking',
    name: 'Internal Linking',
    desc: 'Automatic internal link suggestions and management.',
    icon: '🔗'
  }, {
    key: 'module_schema',
    name: 'Schema Markup',
    desc: 'Add structured data for rich search results.',
    icon: '📊'
  }, {
    key: 'module_social_cards',
    name: 'Social Cards',
    desc: 'Dynamic Open Graph and Twitter Card generation.',
    icon: '🃏'
  }, {
    key: 'module_breadcrumbs',
    name: 'Breadcrumbs',
    desc: 'SEO-friendly breadcrumb navigation.',
    icon: '🥖'
  }, {
    key: 'module_llm_txt',
    name: 'LLM.txt',
    desc: 'Generate llm.txt file for AI crawlers and LLMs.',
    icon: '🤖'
  }, {
    key: 'module_local_seo',
    name: 'Local SEO',
    desc: 'Local business schema and location pages.',
    icon: '📍'
  }, {
    key: 'module_ai_assistant',
    name: 'AI Assistant',
    desc: 'AI-powered content optimization suggestions.',
    icon: '✨'
  }, {
    key: 'module_search_console',
    name: 'Search Console',
    desc: 'Google Search Console integration.',
    icon: '🔍'
  }];
  const enabledCount = modules.filter(m => settings[m.key]).length;

  // Handle module toggle with analytics tracking
  const handleModuleToggle = (key, enabled) => {
    updateSetting(key, enabled);
    _utils_analytics__WEBPACK_IMPORTED_MODULE_6__.analytics.settings.moduleToggle(key.replace('module_', ''), enabled);
  };

  // Handle analytics toggle separately (it's special)
  const handleAnalyticsToggle = enabled => {
    updateSetting('module_analytics', enabled);
    // Note: Can't track disabling analytics since it would disable itself
    if (enabled) {
      _utils_analytics__WEBPACK_IMPORTED_MODULE_6__.analytics.settings.moduleToggle('analytics', true);
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-layout"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-main"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_AnalyticsNotice__WEBPACK_IMPORTED_MODULE_4__["default"], {
    isEnabled: settings.module_analytics,
    onToggle: handleAnalyticsToggle
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "panel-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Feature Modules"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Enable or disable plugin features. Disabled modules are completely unloaded for performance.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "module-count"
  }, enabledCount, " of ", modules.length, " enabled")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "modules-grid"
  }, modules.map(module => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: module.key,
    className: `module-card ${settings[module.key] ? 'active' : ''}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "module-card__icon"
  }, module.icon), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "module-card__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, module.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, module.desc)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings[module.key],
    onChange: e => handleModuleToggle(module.key, e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }))))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("aside", {
    className: "settings-sidebar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Performance Tip"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Disable modules you don't use to reduce database queries and improve page load times.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card warning"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Dependencies"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Some modules require others. For example, \"404 Error Log\" works best with \"Redirects\" enabled."))));
};

// Social Tab
const SocialTab = ({
  settings,
  updateSetting
}) => {
  const sampleTitle = 'Your Page Title';
  const sampleDesc = 'Your page description will appear here when shared on social media platforms.';
  const sampleDomain = 'yoursite.com';
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-layout"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-main"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Open Graph Defaults"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Default settings for Facebook and other social platforms."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "default-og-image"
  }, "Default Share Image"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Used when no featured image is available. Recommended: 1200x630px.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "default-og-image",
    type: "url",
    value: settings.default_og_image,
    onChange: e => updateSetting('default_og_image', e.target.value),
    placeholder: "https://example.com/share-image.jpg"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "fb-app-id"
  }, "Facebook App ID"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "For Facebook Insights.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "fb-app-id",
    type: "text",
    value: settings.facebook_app_id,
    onChange: e => updateSetting('facebook_app_id', e.target.value),
    placeholder: "123456789"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "fb-admin-id"
  }, "Facebook Admin ID")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "fb-admin-id",
    type: "text",
    value: settings.facebook_admin_id,
    onChange: e => updateSetting('facebook_admin_id', e.target.value),
    placeholder: "123456789"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Twitter/X Settings"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Configure Twitter Card appearance."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Card Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "How your content appears on Twitter.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "radio-group"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "radio-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "twitter_card",
    checked: settings.twitter_card_type === 'summary',
    onChange: () => updateSetting('twitter_card_type', 'summary')
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Summary")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "radio-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "twitter_card",
    checked: settings.twitter_card_type === 'summary_large_image',
    onChange: () => updateSetting('twitter_card_type', 'summary_large_image')
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Large Image"))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "twitter-username"
  }, "Twitter Username"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Your @handle without the @.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "input-with-prefix"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "input-prefix"
  }, "@"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "twitter-username",
    type: "text",
    value: settings.twitter_username,
    onChange: e => updateSetting('twitter_username', e.target.value),
    placeholder: "username"
  })))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("aside", {
    className: "settings-sidebar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview social-preview--facebook"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "20",
    height: "20",
    viewBox: "0 0 24 24",
    fill: "#1877f2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Facebook")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__image",
    style: {
      backgroundImage: settings.default_og_image ? `url(${settings.default_og_image})` : 'none'
    }
  }, !settings.default_og_image && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__placeholder"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "48",
    height: "48",
    viewBox: "0 0 24 24",
    fill: "currentColor",
    opacity: "0.3"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__body"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "social-preview__domain"
  }, sampleDomain), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "social-preview__title"
  }, sampleTitle), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "social-preview__desc"
  }, sampleDesc)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `social-preview social-preview--twitter ${settings.twitter_card_type === 'summary' ? 'social-preview--summary' : ''}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "18",
    height: "18",
    viewBox: "0 0 24 24",
    fill: "#000"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "X (Twitter)")), settings.twitter_card_type === 'summary' ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__card social-preview__card--horizontal"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__image social-preview__image--square",
    style: {
      backgroundImage: settings.default_og_image ? `url(${settings.default_og_image})` : 'none'
    }
  }, !settings.default_og_image && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__placeholder"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "32",
    height: "32",
    viewBox: "0 0 24 24",
    fill: "currentColor",
    opacity: "0.3"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__body"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "social-preview__title"
  }, sampleTitle), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "social-preview__desc"
  }, sampleDesc), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "social-preview__domain"
  }, sampleDomain))) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__image",
    style: {
      backgroundImage: settings.default_og_image ? `url(${settings.default_og_image})` : 'none'
    }
  }, !settings.default_og_image && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__placeholder"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "48",
    height: "48",
    viewBox: "0 0 24 24",
    fill: "currentColor",
    opacity: "0.3"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "social-preview__domain-overlay"
  }, sampleDomain)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "social-preview__body"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "social-preview__title"
  }, sampleTitle), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "social-preview__desc"
  }, sampleDesc))))));
};

// Advanced Tab
const AdvancedTab = ({
  settings,
  updateSetting
}) => {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-layout"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-main"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "WordPress Head Cleanup"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Remove unnecessary tags from your site's <head> section."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-grid"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Remove Shortlinks")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.remove_shortlinks,
    onChange: e => updateSetting('remove_shortlinks', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Remove RSD Link")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.remove_rsd_link,
    onChange: e => updateSetting('remove_rsd_link', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Remove WLW Manifest")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.remove_wlwmanifest,
    onChange: e => updateSetting('remove_wlwmanifest', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Remove WP Generator")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.remove_wp_generator,
    onChange: e => updateSetting('remove_wp_generator', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Remove Feed Links")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.remove_feed_links,
    onChange: e => updateSetting('remove_feed_links', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Disable Emoji Scripts")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.disable_emoji,
    onChange: e => updateSetting('disable_emoji', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Content Analysis"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Features for content optimization in the editor."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Link Suggestions"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Show internal link suggestions while editing.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.enable_link_suggestions,
    onChange: e => updateSetting('enable_link_suggestions', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Internal Link Counter"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Show count of internal links in post list.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.enable_internal_link_count,
    onChange: e => updateSetting('enable_internal_link_count', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Cornerstone Content"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Enable cornerstone content marking.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.enable_cornerstone_content,
    onChange: e => updateSetting('enable_cornerstone_content', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Performance"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Optimize plugin performance."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Cache Schema Output"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Cache generated schema markup.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.cache_schema,
    onChange: e => updateSetting('cache_schema', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Minify Schema"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Minify JSON-LD output.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.minify_schema_output,
    onChange: e => updateSetting('minify_schema_output', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Purge Cache on Save"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Clear caches when posts are updated.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.purge_on_save,
    onChange: e => updateSetting('purge_on_save', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel panel--deprecated"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "API Keys ", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "deprecated-badge"
  }, "Deprecated")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "API key management has moved to WP AI Pilot for centralized AI configuration."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "deprecation-notice deprecation-notice--block"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "deprecation-notice__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "deprecation-notice__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "AI Features Now Powered by WP AI Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Configure your OpenAI, Anthropic, Google AI, and other API keys in WP AI Pilot. This provides unified AI management across all your WordPress plugins that support it."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "deprecation-notice__actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "admin.php?page=wp-ai-pilot",
    className: "button primary"
  }, "Open WP AI Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "plugin-install.php?s=wp+ai+pilot&tab=search",
    className: "button ghost"
  }, "Install WP AI Pilot")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("details", {
    className: "legacy-settings-toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("summary", null, "Show legacy API key fields (for reference only)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "legacy-settings-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "These fields are read-only. Use WP AI Pilot to manage API keys."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "openai-key"
  }, "OpenAI API Key")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "openai-key",
    type: "password",
    value: settings.openai_api_key ? '••••••••••••' : '',
    disabled: true,
    placeholder: "Configured in WP AI Pilot"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "google-key"
  }, "Google API Key")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "google-key",
    type: "password",
    value: settings.google_api_key ? '••••••••••••' : '',
    disabled: true,
    placeholder: "Configured in WP AI Pilot"
  })))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Developer"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Options for developers and debugging."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Enable REST API"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Allow external access to SEO data via REST.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.enable_rest_api,
    onChange: e => updateSetting('enable_rest_api', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Debug Mode"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Enable verbose logging and debug output.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.debug_mode,
    onChange: e => updateSetting('debug_mode', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  })))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("aside", {
    className: "settings-sidebar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card warning"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Caution"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Changes to advanced settings may affect site functionality. Make sure you understand what each option does."))));
};

// Tools Tab
const ToolsTab = ({
  settings,
  onExport,
  onImport,
  onReset,
  onResetWizard,
  resettingWizard,
  importFile,
  setImportFile
}) => {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-layout"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-main"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Import / Export"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Backup your settings or transfer them to another site."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tools-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-action"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Export Settings"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Download all plugin settings as a JSON file."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: onExport
  }, "Export Settings")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-action"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Import Settings"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Upload a previously exported JSON file."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "import-controls"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "file",
    accept: ".json",
    onChange: e => setImportFile(e.target.files[0]),
    id: "import-file"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: "import-file",
    className: "button ghost"
  }, importFile ? importFile.name : 'Choose File'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button",
    onClick: onImport,
    disabled: !importFile
  }, "Import"))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Database Tools"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Manage plugin data stored in your database."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tools-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-action"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Clear Cache"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Clear all cached SEO data (schema, sitemaps, etc)."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost"
  }, "Clear Cache")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-action"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Reindex Content"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Rebuild internal link index and content analysis."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost"
  }, "Reindex")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Setup Wizard"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Run the setup wizard again to reconfigure the plugin."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tools-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-action"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Reset Setup Wizard"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Show the setup wizard on next page load. Existing settings will be preserved."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: onResetWizard,
    disabled: resettingWizard
  }, resettingWizard ? 'Resetting...' : 'Reset Wizard')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel danger-zone"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Danger Zone"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "panel-desc"
  }, "Destructive actions that cannot be undone."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tools-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-action"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Reset to Defaults"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Reset all settings to their default values."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button danger",
    onClick: onReset
  }, "Reset All Settings")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-action"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Delete All Data"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Remove all plugin data including redirects, 404 logs, and meta."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button danger"
  }, "Delete All Data"))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("aside", {
    className: "settings-sidebar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card highlight"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Plugin Info"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "info-rows"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "info-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Version"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", null, "0.2.0")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "info-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Interface"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", null, "React SPA")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "info-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "PHP"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", null, "8.1")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "info-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "WordPress"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", null, "6.4")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Need Help?"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Check the documentation or contact support."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "#",
    className: "button ghost"
  }, "View Documentation")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Legacy Interface"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Access V1 for features not yet migrated."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "admin.php?page=wpseopilot",
    className: "button ghost"
  }, "Open V1"))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Settings);

/***/ },

/***/ "./src-v2/pages/Setup.js"
/*!*******************************!*\
  !*** ./src-v2/pages/Setup.js ***!
  \*******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);




/**
 * Setup Wizard - First-time plugin configuration
 */
const Setup = ({
  onComplete,
  onSkip
}) => {
  const [step, setStep] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(1);
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [testingApi, setTestingApi] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [apiTestResult, setApiTestResult] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);

  // Form data across all steps
  const [data, setData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    // Step 2: Site Info
    site_type: '',
    primary_goal: '',
    industry: '',
    // Step 3: AI Config
    ai_provider: 'openai',
    ai_api_key: '',
    ai_model: 'gpt-4o-mini',
    // Step 4: Quick Wins
    enable_sitemap: true,
    enable_404_log: true,
    enable_redirects: true,
    title_template: '%title% - %sitename%'
  });
  const updateData = (key, value) => {
    setData(prev => ({
      ...prev,
      [key]: value
    }));
    setApiTestResult(null);
  };
  const handleNext = () => {
    if (step < 5) setStep(step + 1);
  };
  const handleBack = () => {
    if (step > 1) setStep(step - 1);
  };
  const handleTestApi = async () => {
    if (!data.ai_api_key) return;
    setTestingApi(true);
    setApiTestResult(null);
    try {
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/setup/test-api',
        method: 'POST',
        data: {
          provider: data.ai_provider,
          api_key: data.ai_api_key,
          model: data.ai_model
        }
      });
      setApiTestResult(response.success ? 'success' : 'error');
    } catch (err) {
      setApiTestResult('error');
    } finally {
      setTestingApi(false);
    }
  };
  const handleComplete = async () => {
    setLoading(true);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/setup/complete',
        method: 'POST',
        data
      });
      if (onComplete) onComplete();
    } catch (err) {
      console.error('Failed to save setup:', err);
    } finally {
      setLoading(false);
    }
  };
  const handleSkip = async () => {
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/setup/skip',
        method: 'POST'
      });
    } catch (err) {
      // Ignore errors, just navigate away
    }
    if (onSkip) onSkip();
  };
  const siteTypes = [{
    value: 'blog',
    label: 'Blog / News',
    icon: '📝'
  }, {
    value: 'business',
    label: 'Business / Company',
    icon: '🏢'
  }, {
    value: 'ecommerce',
    label: 'E-commerce / Store',
    icon: '🛒'
  }, {
    value: 'portfolio',
    label: 'Portfolio / Personal',
    icon: '🎨'
  }, {
    value: 'agency',
    label: 'Agency / Services',
    icon: '💼'
  }, {
    value: 'nonprofit',
    label: 'Non-profit / Charity',
    icon: '❤️'
  }];
  const goals = [{
    value: 'traffic',
    label: 'Get more traffic',
    icon: '📈'
  }, {
    value: 'leads',
    label: 'Generate leads',
    icon: '📋'
  }, {
    value: 'sales',
    label: 'Increase sales',
    icon: '💰'
  }, {
    value: 'brand',
    label: 'Build brand awareness',
    icon: '🌟'
  }];
  const providers = [{
    value: 'openai',
    label: 'OpenAI',
    desc: 'GPT-4, GPT-3.5'
  }, {
    value: 'anthropic',
    label: 'Anthropic',
    desc: 'Claude models'
  }, {
    value: 'ollama',
    label: 'Ollama (Local)',
    desc: 'Run locally, free'
  }];
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-wizard"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-progress"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-progress__bar",
    style: {
      width: `${step / 5 * 100}%`
    }
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-content"
  }, step === 1 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-step setup-step--welcome"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-step__icon"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M5 12h14M12 5l7 7-7 7"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Welcome to WP SEO Pilot"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "setup-step__subtitle"
  }, "Let's get your site ready for search engines. This will only take a minute."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-features"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-feature"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-feature__icon"
  }, "\uD83D\uDE80"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "AI-powered optimization")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-feature"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-feature__icon"
  }, "\uD83D\uDCCA"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Real-time SEO analysis")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-feature"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-feature__icon"
  }, "\uD83D\uDD27"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Easy-to-use tools"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary large",
    onClick: handleNext
  }, "Let's Get Started"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: handleSkip
  }, "Skip for now"))), step === 2 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-step"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-step__number"
  }, "Step 1 of 4"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, "Tell us about your site"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "setup-step__subtitle"
  }, "This helps us customize SEO recommendations for your needs."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "setup-label"
  }, "What type of site is this?"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-options setup-options--grid"
  }, siteTypes.map(type => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: type.value,
    type: "button",
    className: `setup-option ${data.site_type === type.value ? 'active' : ''}`,
    onClick: () => updateData('site_type', type.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-option__icon"
  }, type.icon), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-option__label"
  }, type.label))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "setup-label"
  }, "What's your primary goal?"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-options"
  }, goals.map(goal => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: goal.value,
    type: "button",
    className: `setup-option setup-option--horizontal ${data.primary_goal === goal.value ? 'active' : ''}`,
    onClick: () => updateData('primary_goal', goal.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-option__icon"
  }, goal.icon), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-option__label"
  }, goal.label))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "setup-label",
    htmlFor: "industry"
  }, "Industry / Niche (optional)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "industry",
    type: "text",
    className: "setup-input",
    value: data.industry,
    onChange: e => updateData('industry', e.target.value),
    placeholder: "e.g., Technology, Health, Finance..."
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: handleBack
  }, "Back"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleNext,
    disabled: !data.site_type
  }, "Continue"))), step === 3 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-step"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-step__number"
  }, "Step 2 of 4"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, "Connect AI (Optional)"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "setup-step__subtitle"
  }, "Enable AI-powered features like content suggestions and meta generation."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "setup-label"
  }, "Choose a provider"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-providers"
  }, providers.map(provider => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: provider.value,
    type: "button",
    className: `setup-provider ${data.ai_provider === provider.value ? 'active' : ''}`,
    onClick: () => updateData('ai_provider', provider.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, provider.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, provider.desc))))), data.ai_provider !== 'ollama' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "setup-label",
    htmlFor: "api-key"
  }, "API Key"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-input-group"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    id: "api-key",
    type: "password",
    className: "setup-input",
    value: data.ai_api_key,
    onChange: e => updateData('ai_api_key', e.target.value),
    placeholder: data.ai_provider === 'openai' ? 'sk-...' : 'sk-ant-...'
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button",
    onClick: handleTestApi,
    disabled: !data.ai_api_key || testingApi
  }, testingApi ? 'Testing...' : 'Test')), apiTestResult === 'success' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-test-result setup-test-result--success"
  }, "Connection successful!"), apiTestResult === 'error' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-test-result setup-test-result--error"
  }, "Connection failed. Check your API key."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "setup-help"
  }, data.ai_provider === 'openai' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, "Get your API key from ", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://platform.openai.com/api-keys",
    target: "_blank",
    rel: "noopener noreferrer"
  }, "OpenAI Dashboard")), data.ai_provider === 'anthropic' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, "Get your API key from ", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://console.anthropic.com/",
    target: "_blank",
    rel: "noopener noreferrer"
  }, "Anthropic Console")))), data.ai_provider === 'ollama' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-info-box"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, "Ollama Setup"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Make sure Ollama is running locally on port 11434. No API key required."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://ollama.ai",
    target: "_blank",
    rel: "noopener noreferrer"
  }, "Download Ollama")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: handleBack
  }, "Back"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: handleNext
  }, "Skip AI Setup"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleNext,
    disabled: data.ai_provider !== 'ollama' && !data.ai_api_key
  }, "Continue"))), step === 4 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-step"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-step__number"
  }, "Step 3 of 4"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, "Quick Wins"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "setup-step__subtitle"
  }, "Enable these essential features to get started quickly."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-toggles"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "setup-toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-toggle__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "XML Sitemap"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Help search engines discover your content")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: data.enable_sitemap,
    onChange: e => updateData('enable_sitemap', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-toggle__switch"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "setup-toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-toggle__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "404 Error Logging"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Track broken links and fix them")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: data.enable_404_log,
    onChange: e => updateData('enable_404_log', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-toggle__switch"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "setup-toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-toggle__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Redirects Manager"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Create and manage URL redirects")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: data.enable_redirects,
    onChange: e => updateData('enable_redirects', e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-toggle__switch"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "setup-label"
  }, "Title Template"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    className: "setup-select",
    value: data.title_template,
    onChange: e => updateData('title_template', e.target.value)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "%title% - %sitename%"
  }, "Page Title - Site Name"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "%title% | %sitename%"
  }, "Page Title | Site Name"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "%sitename% - %title%"
  }, "Site Name - Page Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "%title%"
  }, "Page Title Only")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "setup-help"
  }, "How titles will appear in search results.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: handleBack
  }, "Back"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleNext
  }, "Continue"))), step === 5 && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-step setup-step--done"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-step__icon setup-step__icon--success"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M20 6L9 17l-5-5"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "You're All Set!"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "setup-step__subtitle"
  }, "WP SEO Pilot is configured and ready to help you rank higher."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-summary"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-summary__item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-summary__label"
  }, "Site Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-summary__value"
  }, siteTypes.find(t => t.value === data.site_type)?.label || 'Not set')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-summary__item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-summary__label"
  }, "AI Provider"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-summary__value"
  }, data.ai_api_key || data.ai_provider === 'ollama' ? providers.find(p => p.value === data.ai_provider)?.label : 'Not configured')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-summary__item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-summary__label"
  }, "Features Enabled"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "setup-summary__value"
  }, [data.enable_sitemap && 'Sitemap', data.enable_404_log && '404 Log', data.enable_redirects && 'Redirects'].filter(Boolean).join(', ') || 'None'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "setup-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary large",
    onClick: handleComplete,
    disabled: loading
  }, loading ? 'Saving...' : 'Go to Dashboard')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "setup-note"
  }, "You can change these settings anytime in Settings."))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Setup);

/***/ },

/***/ "./src-v2/pages/Sitemap.js"
/*!*********************************!*\
  !*** ./src-v2/pages/Sitemap.js ***!
  \*********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _components_SubTabs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/SubTabs */ "./src-v2/components/SubTabs.js");
/* harmony import */ var _hooks_useUrlTab__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../hooks/useUrlTab */ "./src-v2/hooks/useUrlTab.js");





const sitemapTabs = [{
  id: 'xml-sitemap',
  label: 'XML Sitemap'
}, {
  id: 'llm-txt',
  label: 'LLM.txt'
}];
const SCHEDULE_OPTIONS = [{
  value: '',
  label: 'Manual only'
}, {
  value: 'hourly',
  label: 'Hourly'
}, {
  value: 'twicedaily',
  label: 'Twice Daily'
}, {
  value: 'daily',
  label: 'Daily'
}, {
  value: 'weekly',
  label: 'Weekly'
}];
const Sitemap = () => {
  const [activeTab, setActiveTab] = (0,_hooks_useUrlTab__WEBPACK_IMPORTED_MODULE_4__["default"])({
    tabs: sitemapTabs,
    defaultTab: 'xml-sitemap'
  });

  // Loading states
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [regenerating, setRegenerating] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);

  // Sitemap settings
  const [settings, setSettings] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    max_urls: 1000,
    enable_index: '1',
    dynamic_generation: '1',
    schedule_updates: '',
    post_types: [],
    taxonomies: [],
    include_author_pages: '0',
    include_date_archives: '0',
    exclude_images: '0',
    enable_rss: '0',
    enable_google_news: '0',
    google_news_name: '',
    google_news_post_types: [],
    additional_pages: [],
    site_url: '',
    sitemap_url: '',
    rss_sitemap_url: '',
    news_sitemap_url: ''
  });

  // LLM settings
  const [llmSettings, setLlmSettings] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    enable_llm_txt: '0',
    llm_txt_title: '',
    llm_txt_description: '',
    llm_txt_posts_per_type: 50,
    llm_txt_include_excerpt: '1',
    llm_txt_url: ''
  });

  // Available post types and taxonomies
  const [postTypes, setPostTypes] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [taxonomies, setTaxonomies] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);

  // Stats
  const [stats, setStats] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    total_urls: 0,
    last_regenerated: null
  });

  // Fetch all data
  const fetchData = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(async () => {
    setLoading(true);
    try {
      const [settingsRes, llmRes, postTypesRes, taxonomiesRes, statsRes] = await Promise.all([_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/sitemap/settings'
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/sitemap/llm-settings'
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/sitemap/post-types'
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/sitemap/taxonomies'
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/sitemap/stats'
      })]);
      if (settingsRes.success) setSettings(settingsRes.data);
      if (llmRes.success) setLlmSettings(llmRes.data);
      if (postTypesRes.success) setPostTypes(postTypesRes.data);
      if (taxonomiesRes.success) setTaxonomies(taxonomiesRes.data);
      if (statsRes.success) setStats(statsRes.data);
    } catch (error) {
      console.error('Failed to fetch sitemap data:', error);
    } finally {
      setLoading(false);
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchData();
  }, [fetchData]);

  // Save sitemap settings
  const handleSaveSettings = async () => {
    setSaving(true);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/sitemap/settings',
        method: 'POST',
        data: settings
      });
      // Refetch stats after saving
      const statsRes = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/sitemap/stats'
      });
      if (statsRes.success) setStats(statsRes.data);
    } catch (error) {
      console.error('Failed to save settings:', error);
    } finally {
      setSaving(false);
    }
  };

  // Save LLM settings
  const handleSaveLlmSettings = async () => {
    setSaving(true);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/sitemap/llm-settings',
        method: 'POST',
        data: llmSettings
      });
    } catch (error) {
      console.error('Failed to save LLM settings:', error);
    } finally {
      setSaving(false);
    }
  };

  // Regenerate sitemap
  const handleRegenerate = async () => {
    setRegenerating(true);
    try {
      const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/wpseopilot/v2/sitemap/regenerate',
        method: 'POST'
      });
      if (res.success) {
        setStats(prev => ({
          ...prev,
          last_regenerated: res.data.regenerated_at
        }));
      }
    } catch (error) {
      console.error('Failed to regenerate sitemap:', error);
    } finally {
      setRegenerating(false);
    }
  };

  // Toggle post type selection
  const togglePostType = name => {
    setSettings(prev => {
      const current = Array.isArray(prev.post_types) ? prev.post_types : [];
      const updated = current.includes(name) ? current.filter(pt => pt !== name) : [...current, name];
      return {
        ...prev,
        post_types: updated
      };
    });
  };

  // Toggle taxonomy selection
  const toggleTaxonomy = name => {
    setSettings(prev => {
      const current = Array.isArray(prev.taxonomies) ? prev.taxonomies : [];
      const updated = current.includes(name) ? current.filter(t => t !== name) : [...current, name];
      return {
        ...prev,
        taxonomies: updated
      };
    });
  };

  // Toggle Google News post type
  const toggleNewsPostType = name => {
    setSettings(prev => {
      const current = Array.isArray(prev.google_news_post_types) ? prev.google_news_post_types : [];
      const updated = current.includes(name) ? current.filter(pt => pt !== name) : [...current, name];
      return {
        ...prev,
        google_news_post_types: updated
      };
    });
  };

  // Add additional page
  const addAdditionalPage = () => {
    setSettings(prev => ({
      ...prev,
      additional_pages: [...(prev.additional_pages || []), {
        url: '',
        priority: '0.5'
      }]
    }));
  };

  // Update additional page
  const updateAdditionalPage = (index, field, value) => {
    setSettings(prev => {
      const pages = [...(prev.additional_pages || [])];
      pages[index] = {
        ...pages[index],
        [field]: value
      };
      return {
        ...prev,
        additional_pages: pages
      };
    });
  };

  // Remove additional page
  const removeAdditionalPage = index => {
    setSettings(prev => ({
      ...prev,
      additional_pages: (prev.additional_pages || []).filter((_, i) => i !== index)
    }));
  };

  // Format date
  const formatDate = dateStr => {
    if (!dateStr) return 'Never';
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    const hours = Math.floor(diff / (1000 * 60 * 60));
    if (hours < 1) return 'Just now';
    if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    return date.toLocaleDateString();
  };
  if (loading) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "page-header"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Sitemap"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Configure XML sitemap generation and LLM.txt settings."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "loading-state"
    }, "Loading sitemap settings..."));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Sitemap"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Configure XML sitemap generation and LLM.txt settings.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "header-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: settings.sitemap_url,
    target: "_blank",
    rel: "noopener noreferrer",
    className: "button ghost"
  }, "View Sitemap"), llmSettings.enable_llm_txt === '1' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: llmSettings.llm_txt_url,
    target: "_blank",
    rel: "noopener noreferrer",
    className: "button ghost"
  }, "Open llm.txt"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_SubTabs__WEBPACK_IMPORTED_MODULE_3__["default"], {
    tabs: sitemapTabs,
    activeTab: activeTab,
    onChange: setActiveTab,
    ariaLabel: "Sitemap sections"
  }), activeTab === 'xml-sitemap' ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-body two-column"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "main-column"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "XML Sitemap Settings"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Configure your sitemap generation, content types, and additional options."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Automatic Updates"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Regenerate sitemap on a schedule.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: settings.schedule_updates,
    onChange: e => setSettings(prev => ({
      ...prev,
      schedule_updates: e.target.value
    }))
  }, SCHEDULE_OPTIONS.map(opt => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: opt.value,
    value: opt.value
  }, opt.label))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Max URLs Per Page"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Maximum URLs per sitemap page.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    value: settings.max_urls,
    onChange: e => setSettings(prev => ({
      ...prev,
      max_urls: parseInt(e.target.value, 10) || 1000
    })),
    min: "1",
    max: "50000",
    style: {
      width: '120px'
    }
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Generation Options")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "checkbox-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.enable_index === '1',
    onChange: e => setSettings(prev => ({
      ...prev,
      enable_index: e.target.checked ? '1' : '0'
    }))
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Enable sitemap indexes")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "checkbox-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.dynamic_generation === '1',
    onChange: e => setSettings(prev => ({
      ...prev,
      dynamic_generation: e.target.checked ? '1' : '0'
    }))
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Dynamic generation on-demand")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "checkbox-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.exclude_images === '1',
    onChange: e => setSettings(prev => ({
      ...prev,
      exclude_images: e.target.checked ? '1' : '0'
    }))
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Exclude images from entries")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Post Types"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Include in sitemap.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "checkbox-grid"
  }, postTypes.map(pt => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    key: pt.name,
    className: "checkbox-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: (settings.post_types || []).includes(pt.name),
    onChange: () => togglePostType(pt.name)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, pt.label, " (", pt.count, ")")))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Taxonomies"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Include taxonomy archives.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "checkbox-grid"
  }, taxonomies.map(tax => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    key: tax.name,
    className: "checkbox-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: (settings.taxonomies || []).includes(tax.name),
    onChange: () => toggleTaxonomy(tax.name)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, tax.label, " (", tax.count, ")")))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Archive Pages")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "checkbox-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.include_author_pages === '1',
    onChange: e => setSettings(prev => ({
      ...prev,
      include_author_pages: e.target.checked ? '1' : '0'
    }))
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Include author pages")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "checkbox-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.include_date_archives === '1',
    onChange: e => setSettings(prev => ({
      ...prev,
      include_date_archives: e.target.checked ? '1' : '0'
    }))
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Include date archives")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "RSS Sitemap")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "checkbox-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.enable_rss === '1',
    onChange: e => setSettings(prev => ({
      ...prev,
      enable_rss: e.target.checked ? '1' : '0'
    }))
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Generate RSS sitemap (latest 50 posts)")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Google News")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "checkbox-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: settings.enable_google_news === '1',
    onChange: e => setSettings(prev => ({
      ...prev,
      enable_google_news: e.target.checked ? '1' : '0'
    }))
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Enable Google News sitemap")), settings.enable_google_news === '1' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      marginTop: '12px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    value: settings.google_news_name,
    onChange: e => setSettings(prev => ({
      ...prev,
      google_news_name: e.target.value
    })),
    placeholder: "Publication Name",
    style: {
      width: '100%',
      marginBottom: '8px'
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "checkbox-grid"
  }, postTypes.map(pt => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    key: pt.name,
    className: "checkbox-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: (settings.google_news_post_types || []).includes(pt.name),
    onChange: () => toggleNewsPostType(pt.name)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, pt.label))))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Custom Pages"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Add URLs not managed by WordPress.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "additional-pages-list"
  }, (settings.additional_pages || []).map((page, index) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: index,
    className: "additional-page-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "url",
    value: page.url,
    onChange: e => updateAdditionalPage(index, 'url', e.target.value),
    placeholder: "https://example.com/page",
    className: "page-url-input"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    value: page.priority,
    onChange: e => updateAdditionalPage(index, 'priority', e.target.value),
    placeholder: "0.5",
    className: "page-priority-input"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost small",
    onClick: () => removeAdditionalPage(index)
  }, "Remove")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost small",
    onClick: addAdditionalPage
  }, "+ Add Page")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-actions",
    style: {
      marginTop: '24px',
      paddingTop: '24px',
      borderTop: '1px solid #e5e7eb'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleSaveSettings,
    disabled: saving
  }, saving ? 'Saving...' : 'Save Changes'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button ghost",
    onClick: handleRegenerate,
    disabled: regenerating
  }, regenerating ? 'Regenerating...' : 'Regenerate Now')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("aside", {
    className: "side-panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card highlight"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Your Sitemaps"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "sitemap-links"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "sitemap-link-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Main Index"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: settings.sitemap_url,
    target: "_blank",
    rel: "noopener noreferrer"
  }, settings.sitemap_url)), settings.enable_rss === '1' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "sitemap-link-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "RSS Feed"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: settings.rss_sitemap_url,
    target: "_blank",
    rel: "noopener noreferrer"
  }, settings.rss_sitemap_url)), settings.enable_google_news === '1' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "sitemap-link-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, "Google News"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: settings.news_sitemap_url,
    target: "_blank",
    rel: "noopener noreferrer"
  }, settings.news_sitemap_url)))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Statistics"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stats-list"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "muted"
  }, "Total URLs"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-value"
  }, stats.total_urls.toLocaleString())), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "stat-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "muted"
  }, "Last Updated"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "stat-value"
  }, formatDate(stats.last_regenerated))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Pro Tip"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Submit your sitemap to Google Search Console and Bing Webmaster Tools for faster indexing.")))) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-body two-column"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "main-column"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "LLM.txt Configuration"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Help AI engines discover your content. Similar to XML sitemaps for search engines, llm.txt guides AI crawlers like ChatGPT and Claude.", ' ', (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://llmstxt.org/",
    target: "_blank",
    rel: "noopener noreferrer"
  }, "Learn more")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Enable llm.txt"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Generate and serve the llm.txt file.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: llmSettings.enable_llm_txt === '1',
    onChange: e => setLlmSettings(prev => ({
      ...prev,
      enable_llm_txt: e.target.checked ? '1' : '0'
    }))
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-track"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "toggle-text"
  }, llmSettings.enable_llm_txt === '1' ? 'Enabled' : 'Disabled')))), llmSettings.enable_llm_txt === '1' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Title"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Main title in your llm.txt file.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    value: llmSettings.llm_txt_title,
    onChange: e => setLlmSettings(prev => ({
      ...prev,
      llm_txt_title: e.target.value
    })),
    placeholder: "Defaults to site name"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Description"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Brief description below the title.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("textarea", {
    value: llmSettings.llm_txt_description,
    onChange: e => setLlmSettings(prev => ({
      ...prev,
      llm_txt_description: e.target.value
    })),
    placeholder: "Defaults to site tagline",
    rows: "3"
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Max Posts Per Type"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Limit posts included per type (1-500).")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "number",
    value: llmSettings.llm_txt_posts_per_type,
    onChange: e => setLlmSettings(prev => ({
      ...prev,
      llm_txt_posts_per_type: parseInt(e.target.value, 10) || 50
    })),
    min: "1",
    max: "500",
    style: {
      width: '120px'
    }
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Options")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "checkbox-row"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: llmSettings.llm_txt_include_excerpt === '1',
    onChange: e => setLlmSettings(prev => ({
      ...prev,
      llm_txt_include_excerpt: e.target.checked ? '1' : '0'
    }))
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "Include post excerpts/descriptions")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-row compact"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-label"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Content Included"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "settings-help"
  }, "Post types in your llm.txt file.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "settings-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "post-types-preview"
  }, postTypes.map(pt => {
    const willInclude = Math.min(pt.count, llmSettings.llm_txt_posts_per_type);
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      key: pt.name,
      className: "post-type-preview-item"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "post-type-info"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, pt.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "muted"
    }, willInclude, " of ", pt.count, " posts")));
  })))))), llmSettings.enable_llm_txt === '1' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "form-actions",
    style: {
      marginTop: '24px',
      paddingTop: '24px',
      borderTop: '1px solid #e5e7eb'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button primary",
    onClick: handleSaveLlmSettings,
    disabled: saving
  }, saving ? 'Saving...' : 'Save Changes'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: llmSettings.llm_txt_url,
    target: "_blank",
    rel: "noopener noreferrer",
    className: "button ghost"
  }, "View llm.txt")))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("aside", {
    className: "side-panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card highlight"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Your llm.txt"), llmSettings.enable_llm_txt === '1' ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("code", {
    className: "url-display"
  }, llmSettings.llm_txt_url), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted",
    style: {
      marginTop: '12px',
      fontSize: '13px'
    }
  }, "If not accessible, go to Settings > Permalinks and save to flush rewrite rules.")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "Enable llm.txt to generate your file.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "side-card"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "What is llm.txt?"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted"
  }, "A standardized file that helps AI language models like ChatGPT, Claude, and Gemini discover and understand your content structure."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "muted",
    style: {
      marginTop: '8px'
    }
  }, "This improves how AI systems reference and cite your content when answering questions."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://llmstxt.org/",
    target: "_blank",
    rel: "noopener noreferrer",
    className: "button ghost small",
    style: {
      marginTop: '12px'
    }
  }, "Learn More")))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Sitemap);

/***/ },

/***/ "./src-v2/pages/Tools.js"
/*!*******************************!*\
  !*** ./src-v2/pages/Tools.js ***!
  \*******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);



// AI Assistants
const aiAssistants = [{
  id: 'general-seo',
  name: 'SEO Assistant',
  description: 'Your helpful SEO buddy for all things search optimization. Ask about meta tags, keywords, content, and more.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "11",
    cy: "11",
    r: "8"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 21l-4.35-4.35"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M11 8v6M8 11h6"
  })),
  color: '#2271b1'
}, {
  id: 'seo-reporter',
  name: 'SEO Reporter',
  description: 'Get quick reports on your site\'s SEO health. Find issues, track improvements, and get actionable tips.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "9",
    y: "3",
    width: "6",
    height: "4",
    rx: "1"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 12h6M9 16h6"
  })),
  color: '#00a32a'
}];

// AI-Powered Tools
const aiTools = [{
  id: 'bulk-editor',
  name: 'Smart Bulk Editor',
  description: 'Edit SEO titles and descriptions in bulk with AI-powered suggestions. Spreadsheet view for efficient editing.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
  })),
  color: '#6366f1'
}, {
  id: 'content-gaps',
  name: 'Content Gaps Finder',
  description: 'AI finds what you should write about. Discover missing topics and get content outlines automatically.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "9",
    y: "3",
    width: "6",
    height: "4",
    rx: "1"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 12v4M10 14h4"
  })),
  color: '#059669'
}, {
  id: 'schema-builder',
  name: 'Schema Builder',
  description: 'Visual drag-and-drop schema creation. Generate structured data for rich search results with AI assistance.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M16 18l2-2-2-2M8 18l-2-2 2-2M14 4l-4 16"
  })),
  color: '#8b5cf6'
}];

// Tools data sorted by popularity
const tools = [
// Popular Tools (working)
{
  id: 'redirects',
  name: 'Redirects',
  description: 'Create and manage URL redirects. Handle 301, 302, and 307 redirects to maintain SEO value when URLs change.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 18l6-6-6-6"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M15 6l-6 6 6 6",
    opacity: "0.5"
  })),
  color: '#2271b1',
  popular: true
}, {
  id: '404-log',
  name: '404 Log',
  description: 'Track and fix broken links. Monitor 404 errors in real-time and quickly create redirects to fix them.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "10"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 8v4m0 4h.01"
  })),
  color: '#d63638',
  popular: true
}, {
  id: 'audit',
  name: 'SEO Audit',
  description: 'Analyze your site for SEO issues. Get actionable recommendations to improve rankings and fix problems.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "9",
    y: "3",
    width: "6",
    height: "4",
    rx: "1"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 12l2 2 4-4"
  })),
  color: '#00a32a',
  popular: true
}, {
  id: 'ai-assistant',
  name: 'AI Assistant',
  description: 'Generate SEO-optimized content with AI. Create titles, descriptions, and content suggestions automatically.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "9",
    cy: "13",
    r: "1"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "15",
    cy: "13",
    r: "1"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 17h6"
  })),
  color: '#8b5cf6',
  popular: true
},
// More Tools (working)
{
  id: 'internal-linking',
  name: 'Internal Linking',
  description: 'Discover and manage internal link opportunities. Build a stronger site structure for better SEO.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"
  })),
  color: '#0891b2',
  popular: false
}, {
  id: 'robots-txt',
  name: 'Robots.txt Editor',
  description: 'View and edit your robots.txt file. Control how search engines crawl your site.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "3",
    y: "3",
    width: "18",
    height: "18",
    rx: "2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 9h6M9 13h6M9 17h4"
  })),
  color: '#64748b',
  popular: false,
  comingSoon: true
}, {
  id: 'htaccess-editor',
  name: '.htaccess Editor',
  description: 'Safely edit your .htaccess file. Manage redirects, security rules, and server configuration.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"
  })),
  color: '#be185d',
  popular: false,
  comingSoon: true
}, {
  id: 'sitemap-validator',
  name: 'Sitemap Validator',
  description: 'Test and validate your XML sitemap. Ensure all URLs are accessible and properly formatted.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 2L2 7l10 5 10-5-10-5z"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M2 17l10 5 10-5M2 12l10 5 10-5"
  })),
  color: '#0d9488',
  popular: false,
  comingSoon: true
}, {
  id: 'meta-validator',
  name: 'Meta Tag Tester',
  description: 'Test any URL for SEO meta tags. Validate titles, descriptions, OG tags, and Twitter cards.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "8",
    y: "2",
    width: "8",
    height: "4",
    rx: "1"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 12h6M9 16h6"
  })),
  color: '#7c3aed',
  popular: false,
  comingSoon: true
}, {
  id: 'schema-validator',
  name: 'Schema Validator',
  description: 'Test structured data on any page. Validate JSON-LD, Microdata, and RDFa markup.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M4 6h16M4 12h16M4 18h10"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "19",
    cy: "18",
    r: "3"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M19 16v2h2"
  })),
  color: '#ea580c',
  popular: false,
  comingSoon: true
}, {
  id: 'heading-analyzer',
  name: 'Heading Analyzer',
  description: 'Analyze heading structure of any page. Check H1-H6 hierarchy and find issues.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M4 12h8M4 18V6M12 18V6M20 7v10M16 7h8M16 12h6"
  })),
  color: '#0284c7',
  popular: false,
  comingSoon: true
}, {
  id: 'link-checker',
  name: 'Broken Link Checker',
  description: 'Scan pages for broken links. Find and fix dead links before they hurt your SEO.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M15 7h3a5 5 0 010 10h-3m-6 0H6a5 5 0 010-10h3"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("line", {
    x1: "8",
    y1: "12",
    x2: "16",
    y2: "12"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M2 2l20 20",
    strokeLinecap: "round"
  })),
  color: '#dc2626',
  popular: false,
  comingSoon: true
}, {
  id: 'page-speed',
  name: 'Page Speed Test',
  description: 'Test page load performance. Get Core Web Vitals scores and optimization tips.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "12",
    cy: "12",
    r: "10"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 6v6l4 2"
  })),
  color: '#16a34a',
  popular: false,
  comingSoon: true
}, {
  id: 'mobile-test',
  name: 'Mobile Friendly Test',
  description: 'Check if pages are mobile-friendly. Identify viewport and touch issues.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "5",
    y: "2",
    width: "14",
    height: "20",
    rx: "2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M12 18h.01"
  })),
  color: '#2563eb',
  popular: false,
  comingSoon: true
}, {
  id: 'image-optimizer',
  name: 'Image SEO Checker',
  description: 'Analyze images for SEO. Check alt texts, file sizes, and compression opportunities.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
    x: "3",
    y: "3",
    width: "18",
    height: "18",
    rx: "2"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    cx: "8.5",
    cy: "8.5",
    r: "1.5"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M21 15l-5-5L5 21"
  })),
  color: '#f59e0b',
  popular: false,
  comingSoon: true
},
// Coming Soon
{
  id: 'schema-generator',
  name: 'Schema Generator',
  description: 'Generate structured data markup for rich snippets. Support for articles, products, FAQs, and more.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M16 18l2-2-2-2M8 18l-2-2 2-2M14 4l-4 16"
  })),
  color: '#8b5cf6',
  popular: false,
  comingSoon: true
}, {
  id: 'keyword-tracker',
  name: 'Keyword Tracker',
  description: 'Monitor keyword rankings over time. Track your positions in search results and see trends.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M3 3v18h18"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M18 9l-5 5-4-4-3 3"
  })),
  color: '#059669',
  popular: false,
  comingSoon: true
}, {
  id: 'bulk-editor',
  name: 'Bulk Editor',
  description: 'Edit SEO titles and descriptions in bulk. Make changes to multiple posts at once efficiently.',
  icon: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
  })),
  color: '#6366f1',
  popular: false,
  comingSoon: true
}];
const Tools = ({
  onNavigate
}) => {
  const [hoveredTool, setHoveredTool] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const handleToolClick = tool => {
    if (tool.comingSoon) return;

    // Map tool IDs to view IDs (most map directly)
    const viewMap = {
      'redirects': 'redirects',
      '404-log': '404-log',
      'audit': 'audit',
      'ai-assistant': 'ai-assistant',
      'internal-linking': 'internal-linking',
      'robots-txt': 'robots-txt',
      'htaccess-editor': 'htaccess-editor',
      'sitemap-validator': 'sitemap-validator',
      'meta-validator': 'meta-validator',
      'schema-validator': 'schema-validator',
      'heading-analyzer': 'heading-analyzer',
      'link-checker': 'link-checker',
      'page-speed': 'page-speed',
      'mobile-test': 'mobile-test',
      'image-optimizer': 'image-optimizer',
      'bulk-editor': 'bulk-editor',
      'content-gaps': 'content-gaps',
      'schema-builder': 'schema-builder'
    };
    const viewId = viewMap[tool.id];
    if (viewId && onNavigate) {
      onNavigate(viewId);
    }
  };
  const handleAssistantClick = assistant => {
    if (onNavigate) {
      onNavigate('assistants');
    }
  };

  // Separate popular and other tools
  const popularTools = tools.filter(t => t.popular);
  const otherTools = tools.filter(t => !t.popular);
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "page-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Tools"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "SEO utilities and helpers to optimize your website."))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "tools-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", {
    className: "tools-section__title"
  }, "AI-Powered Tools"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tools-grid tools-grid--large"
  }, aiTools.map(tool => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: tool.id,
    type: "button",
    className: `tool-card ${hoveredTool === tool.id ? 'tool-card--hover' : ''}`,
    onClick: () => handleToolClick(tool),
    onMouseEnter: () => setHoveredTool(tool.id),
    onMouseLeave: () => setHoveredTool(null)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__icon",
    style: {
      backgroundColor: `${tool.color}15`,
      color: tool.color
    }
  }, tool.icon), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", {
    className: "tool-card__name"
  }, tool.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "tool-card__badge tool-card__badge--ai"
  }, "AI")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "tool-card__desc"
  }, tool.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__arrow",
    style: {
      color: tool.color
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 18l6-6-6-6"
  }))))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "tools-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", {
    className: "tools-section__title"
  }, "AI Assistants"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tools-grid tools-grid--large"
  }, aiAssistants.map(assistant => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: assistant.id,
    type: "button",
    className: `tool-card ${hoveredTool === assistant.id ? 'tool-card--hover' : ''}`,
    onClick: () => handleAssistantClick(assistant),
    onMouseEnter: () => setHoveredTool(assistant.id),
    onMouseLeave: () => setHoveredTool(null)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__icon",
    style: {
      backgroundColor: `${assistant.color}15`,
      color: assistant.color
    }
  }, assistant.icon), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", {
    className: "tool-card__name"
  }, assistant.name), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "tool-card__badge tool-card__badge--ai"
  }, "AI")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "tool-card__desc"
  }, assistant.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__arrow",
    style: {
      color: assistant.color
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 18l6-6-6-6"
  }))))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "tools-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", {
    className: "tools-section__title"
  }, "Popular Tools"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tools-grid tools-grid--large"
  }, popularTools.map(tool => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: tool.id,
    type: "button",
    className: `tool-card ${tool.comingSoon ? 'tool-card--disabled' : ''} ${hoveredTool === tool.id ? 'tool-card--hover' : ''}`,
    onClick: () => handleToolClick(tool),
    onMouseEnter: () => setHoveredTool(tool.id),
    onMouseLeave: () => setHoveredTool(null),
    disabled: tool.comingSoon
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__icon",
    style: {
      backgroundColor: `${tool.color}15`,
      color: tool.color
    }
  }, tool.icon), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", {
    className: "tool-card__name"
  }, tool.name), tool.comingSoon && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "tool-card__badge"
  }, "Coming Soon")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "tool-card__desc"
  }, tool.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__arrow",
    style: {
      color: tool.color
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: "2"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M9 18l6-6-6-6"
  }))))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("section", {
    className: "tools-section"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", {
    className: "tools-section__title"
  }, "More Tools"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tools-grid tools-grid--small"
  }, otherTools.map(tool => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    key: tool.id,
    type: "button",
    className: `tool-card tool-card--compact ${tool.comingSoon ? 'tool-card--disabled' : ''}`,
    onClick: () => handleToolClick(tool),
    disabled: tool.comingSoon
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__icon tool-card__icon--small",
    style: {
      backgroundColor: `${tool.color}15`,
      color: tool.color
    }
  }, tool.icon), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "tool-card__header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", {
    className: "tool-card__name"
  }, tool.name), tool.comingSoon && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "tool-card__badge"
  }, "Soon")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "tool-card__desc"
  }, tool.description)))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Tools);

/***/ },

/***/ "./src-v2/utils/analytics.js"
/*!***********************************!*\
  !*** ./src-v2/utils/analytics.js ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   analytics: () => (/* binding */ analytics),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   trackEvent: () => (/* binding */ trackEvent),
/* harmony export */   trackPageView: () => (/* binding */ trackPageView)
/* harmony export */ });
/**
 * WP SEO Pilot Analytics Utility
 *
 * Tracks feature usage to help improve the plugin.
 * All tracking is anonymous and privacy-respecting:
 * - No cookies used
 * - No personal data collected
 * - Only tracks within the plugin admin pages
 * - Can be disabled in Settings > Modules > Analytics
 */

// Check if analytics is available and enabled
const isAnalyticsEnabled = () => {
  return typeof window._paq !== 'undefined';
};

/**
 * Track a feature usage event
 *
 * @param {string} category - Event category (e.g., 'Redirects', 'AI', 'Sitemap')
 * @param {string} action - Event action (e.g., 'Create', 'Generate', 'Export')
 * @param {string} name - Optional event name/label
 * @param {number} value - Optional numeric value
 */
const trackEvent = (category, action, name = null, value = null) => {
  if (!isAnalyticsEnabled()) return;
  try {
    const eventData = ['trackEvent', category, action];
    if (name) eventData.push(name);
    if (value !== null) eventData.push(value);
    window._paq.push(eventData);

    // Debug log in development
    if (window.wpseopilotDebug) {
      console.log('WP SEO Pilot Analytics:', {
        category,
        action,
        name,
        value
      });
    }
  } catch (e) {
    // Silently fail - analytics should never break functionality
  }
};

/**
 * Track a page view (for SPA navigation)
 *
 * @param {string} pageName - Page name/title
 * @param {string} pageUrl - Optional custom URL
 */
const trackPageView = (pageName, pageUrl = null) => {
  if (!isAnalyticsEnabled()) return;
  try {
    if (pageUrl) {
      window._paq.push(['setCustomUrl', pageUrl]);
    }
    window._paq.push(['setDocumentTitle', `WP SEO Pilot - ${pageName}`]);
    window._paq.push(['trackPageView']);
  } catch (e) {
    // Silently fail
  }
};

/**
 * Track feature-specific events with predefined categories
 */
const analytics = {
  // Redirects
  redirects: {
    create: type => trackEvent('Redirects', 'Create', type),
    edit: () => trackEvent('Redirects', 'Edit'),
    delete: () => trackEvent('Redirects', 'Delete'),
    import: count => trackEvent('Redirects', 'Import', 'count', count),
    export: count => trackEvent('Redirects', 'Export', 'count', count)
  },
  // Sitemap
  sitemap: {
    generate: () => trackEvent('Sitemap', 'Generate'),
    submit: engine => trackEvent('Sitemap', 'Submit', engine),
    configure: () => trackEvent('Sitemap', 'Configure')
  },
  // AI Features
  ai: {
    generate: type => trackEvent('AI', 'Generate', type),
    // 'title', 'description', 'both'
    assistant: assistantId => trackEvent('AI', 'Assistant', assistantId),
    modelChange: model => trackEvent('AI', 'ModelChange', model),
    customModel: action => trackEvent('AI', 'CustomModel', action) // 'create', 'edit', 'delete', 'test'
  },
  // Search Appearance
  searchAppearance: {
    save: section => trackEvent('SearchAppearance', 'Save', section),
    aiGenerate: field => trackEvent('SearchAppearance', 'AIGenerate', field)
  },
  // Internal Linking
  internalLinking: {
    createRule: () => trackEvent('InternalLinking', 'CreateRule'),
    applySuggestion: () => trackEvent('InternalLinking', 'ApplySuggestion'),
    analyze: () => trackEvent('InternalLinking', 'Analyze')
  },
  // Tools
  tools: {
    bulkEditor: action => trackEvent('Tools', 'BulkEditor', action),
    contentGaps: () => trackEvent('Tools', 'ContentGaps'),
    schemaBuilder: schemaType => trackEvent('Tools', 'SchemaBuilder', schemaType)
  },
  // Settings
  settings: {
    save: tab => trackEvent('Settings', 'Save', tab),
    moduleToggle: (module, enabled) => trackEvent('Settings', 'ModuleToggle', module, enabled ? 1 : 0),
    export: () => trackEvent('Settings', 'Export'),
    import: () => trackEvent('Settings', 'Import'),
    reset: () => trackEvent('Settings', 'Reset')
  },
  // Setup Wizard
  setup: {
    start: () => trackEvent('Setup', 'Start'),
    complete: () => trackEvent('Setup', 'Complete'),
    skip: step => trackEvent('Setup', 'Skip', step)
  },
  // Audit
  audit: {
    run: () => trackEvent('Audit', 'Run'),
    fixIssue: issueType => trackEvent('Audit', 'FixIssue', issueType)
  },
  // 404 Errors
  errors404: {
    createRedirect: () => trackEvent('404Errors', 'CreateRedirect'),
    ignore: () => trackEvent('404Errors', 'Ignore'),
    clear: () => trackEvent('404Errors', 'Clear')
  },
  // Generic tracking for custom events
  track: trackEvent,
  pageView: trackPageView
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (analytics);

/***/ },

/***/ "@wordpress/api-fetch"
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["apiFetch"];

/***/ },

/***/ "@wordpress/element"
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
(module) {

module.exports = window["wp"]["element"];

/***/ },

/***/ "react"
/*!************************!*\
  !*** external "React" ***!
  \************************/
(module) {

module.exports = window["React"];

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*************************!*\
  !*** ./src-v2/index.js ***!
  \*************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _App__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./App */ "./src-v2/App.js");
/* harmony import */ var _index_css__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./index.css */ "./src-v2/index.css");




const initialView = window?.wpseopilotV2Settings?.initialView || 'dashboard';
(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.render)((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_App__WEBPACK_IMPORTED_MODULE_2__["default"], {
  initialView: initialView
}), document.getElementById('wpseopilot-v2-root'));
})();

/******/ })()
;
//# sourceMappingURL=index.js.map