(function(blocks, element, editor) {
    var el = element.createElement;
    var __ = wp.i18n.__;

    blocks.registerBlockType('healthcare-bookmarks/bookmark-counter', {
        title: __('Healthcare Bookmark Counter'),
        icon: 'admin-users',
        category: 'widgets',
        description: __('Shows bookmark count and links to My Bookmarks page'),
        keywords: ['bookmark', 'counter', 'navigation'],
        
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
                el('h4', {
                    style: {
                        margin: '0 0 12px 0',
                        color: '#007cba',
                        fontSize: '16px'
                    }
                }, 'Healthcare Bookmark Counter'),
                el('div', {
                    style: {
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '8px',
                        padding: '8px 12px',
                        background: 'white',
                        border: '1px solid #ddd',
                        borderRadius: '6px'
                    }
                },
                    el('span', { 
                        style: { 
                            width: '18px', 
                            height: '18px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        },
                        dangerouslySetInnerHTML: {
                            __html: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#007cba" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path></svg>'
                        }
                    }),
                    el('span', {
                        style: { fontWeight: '500' }
                    }, 'My Bookmarks'),
                    el('span', { 
                        style: { 
                            background: '#007cba', 
                            color: 'white', 
                            borderRadius: '50%',
                            width: '22px',
                            height: '22px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            fontSize: '12px',
                            fontWeight: 'bold'
                        }
                    }, '0')
                ),
                el('p', {
                    style: {
                        margin: '12px 0 0 0',
                        color: '#666',
                        fontSize: '14px'
                    }
                }, 'Links to My Bookmarks page')
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