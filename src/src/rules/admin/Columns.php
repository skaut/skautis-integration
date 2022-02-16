<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules;

class Columns {

    public function __construct() {
        $this->initHooks();
    }

    protected function initHooks() {
        add_filter( 'manage_edit-' . RulesInit::RULES_TYPE_SLUG . '_columns', [ $this, 'lastModifiedAdminColumn' ] );
        add_filter(
            'manage_edit-' . RulesInit::RULES_TYPE_SLUG . '_sortable_columns',
            [
                $this,
                'sortableLastModifiedColumn',
            ]
        );
        add_action(
            'manage_' . RulesInit::RULES_TYPE_SLUG . '_posts_custom_column',
            [
                $this,
                'lastModifiedAdminColumnContent',
            ],
            10,
            2
        );
    }

    public function lastModifiedAdminColumn( array $columns = [] ): array
    {
        $columns['modified_last'] = __( 'Naposledy upraveno', 'skautis-integration' );

        return $columns;
    }

    public function sortableLastModifiedColumn( array $columns = [] ): array
    {
        $columns['modified_last'] = 'modified';

        return $columns;
    }

    public function lastModifiedAdminColumnContent( string $columnName, int $postId ) {
        if ( 'modified_last' !== $columnName ) {
            return;
        }

        $post = get_post( $postId );
        /* translators: human-readable time difference */
        $modifiedDate   = sprintf( _x( 'Před %s', '%s = human-readable time difference', 'skautis-integration' ), human_time_diff( strtotime( $post->post_modified ), time() ) );
        $modifiedAuthor = get_the_modified_author();

        echo esc_html( $modifiedDate );
        echo '<br>';
        echo '<strong>' . esc_html( $modifiedAuthor ) . '</strong>';
    }

}
