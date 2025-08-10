(function(blocks, element, editor) {
    var el = element.createElement;
    var __ = wp.i18n.__;

    blocks.registerBlockType('healthcare-bookmarks/bookmark-button', {
        title: __('Healthcare Bookmark Button'),
        icon: 'heart',
        category: 'widgets',
        description: __('Add a bookmark button for healthcare providers'),
        keywords: ['bookmark', 'save', 'healthcare'],
        
        supports: {
            align: ['left', 'center', 'right'],
            className: true,
            customClassName: true
        },
        
        edit: function(props) {
            return el('div', {
                className: 'hb-block-preview',
                style: {
                    padding: '20px',
                    border: '2px dashed #007cba',
                    borderRadius: '8px',
                    textAlign: 'center',
                    backgroundColor: '#f8f9fa'
                }
            }, 
                el('div', {
                    style: {
                        fontSize: '24px',
                        marginBottom: '8px'
                    },
                    dangerouslySetInnerHTML: {
                        __html: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#007cba" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path></svg>'
                    }
                }),
                el('h4', {
                    style: {
                        margin: '0 0 8px 0',
                        color: '#007cba',
                        fontSize: '16px'
                    }
                }, 'Healthcare Bookmark Button'),
                el('p', {
                    style: {
                        margin: '0',
                        color: '#666',
                        fontSize: '14px'
                    }
                }, 'Only displays on healthcare_provider posts'),
                el('div', {
                    style: {
                        marginTop: '12px',
                        padding: '8px 16px',
                        background: '#007cba',
                        color: 'white',
                        borderRadius: '4px',
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '8px',
                        fontSize: '14px'
                    }
                }, 
                    el('span', {
                        dangerouslySetInnerHTML: {
                            __html: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path></svg>'
                        }
                    }),
                    'Bookmark'
                )
            );
        },

        save: function() {
            return null; // Dynamic block - rendered by PHP
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.editor
);