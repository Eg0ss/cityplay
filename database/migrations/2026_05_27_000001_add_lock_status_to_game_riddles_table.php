<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_riddles', function (Blueprint $table) {
            $table->string('statut')->default('ouvert')->after('riddle_id');
            $table->foreignId('locked_by_player_id')->nullable()->constrained('game_players')->onDelete('set null')->after('statut');
        });

        DB::unprepared("
            CREATE OR REPLACE FUNCTION lock_game_riddle(
                p_game_riddle_id BIGINT,
                p_game_player_id BIGINT
            ) RETURNS JSON AS \$\$
            DECLARE
                v_riddle RECORD;
                v_result JSON;
            BEGIN
                SELECT * INTO v_riddle
                FROM game_riddles
                WHERE id = p_game_riddle_id
                FOR UPDATE;

                IF v_riddle.statut = 'verrouille' THEN
                    SELECT json_build_object(
                        'success', false,
                        'locked_by_player_id', v_riddle.locked_by_player_id,
                        'message', 'Cette énigme est déjà verrouillée.'
                    ) INTO v_result;
                ELSE
                    UPDATE game_riddles
                    SET statut = 'verrouille',
                        locked_by_player_id = p_game_player_id,
                        verrouille_a = NOW()
                    WHERE id = p_game_riddle_id;

                    SELECT json_build_object(
                        'success', true,
                        'game_riddle_id', p_game_riddle_id,
                        'locked_by_player_id', p_game_player_id
                    ) INTO v_result;
                END IF;

                RETURN v_result;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP FUNCTION IF EXISTS lock_game_riddle(BIGINT, BIGINT);");

        Schema::table('game_riddles', function (Blueprint $table) {
            $table->dropForeign(['locked_by_player_id']);
            $table->dropColumn(['statut', 'locked_by_player_id']);
        });
    }
};