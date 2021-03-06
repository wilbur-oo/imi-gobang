<?php
namespace ImiApp\Module\Gobang\Logic;

use Imi\Bean\Annotation\Bean;
use Imi\Aop\Annotation\Inject;
use ImiApp\Exception\BusinessException;
use ImiApp\Module\Gobang\Enum\GobangCell;
use ImiApp\Module\Gobang\Enum\GobangStatus;
use ImiApp\Module\Gobang\Enum\MessageActions;

/**
 * @Bean("GobangLogic")
 */
class GobangLogic
{
    /**
     * @Inject("RoomService")
     *
     * @var \ImiApp\Module\Gobang\Service\RoomService
     */
    protected $roomService;

    /**
     * @Inject("GobangService")
     *
     * @var \ImiApp\Module\Gobang\Service\GobangService
     */
    protected $gobangService;

    /**
     * @Inject("RoomLogic")
     *
     * @var \ImiApp\Module\Gobang\Logic\RoomLogic
     */
    protected $roomLogic;

    /**
     * @Inject("MemberService")
     *
     * @var \ImiApp\Module\Member\Service\MemberService
     */
    protected $memberService;

    /**
     * 落子
     *
     * @param integer $roomId
     * @param integer $memberId
     * @param integer $x
     * @param integer $y
     * @return void
     */
    public function go(int $roomId, int $memberId, int $x, int $y)
    {
        $game = $this->gobangService->go($roomId, $memberId, $x, $y);
        $data = [];
        $winner = $game->referee($x, $y);
        if(GobangCell::NONE === $winner)
        {
            $data['winner'] = null;
        }
        else
        {
            $room = $this->roomService->getInfo($roomId);
            if($winner === $game->getPlayer1Color())
            {
                $winnerMemberId = $room->getPlayerId1();
            }
            else if($winner === $game->getPlayer2Color())
            {
                $winnerMemberId = $room->getPlayerId2();
            }
            else
            {
                throw new BusinessException('数据错误');
            }
            $room->setPlayer1Ready(false);
            $room->setPlayer2Ready(false);
            $room->setStatus(GobangStatus::WAIT_START);
            $room->save();
            $data['winner'] = $this->memberService->get($winnerMemberId);
            defer(function() use($roomId, $room){
                $this->roomLogic->pushRoomMessage($roomId, MessageActions::ROOM_INFO, [
                    'roomInfo'  =>  $room,
                ]);
            });
        }
        // 棋盘
        // $data['map'] = $game->getGobangMap();
        $data['game'] = $game;
        $this->roomLogic->pushRoomMessage($roomId, MessageActions::GOBANG_INFO, $data);
    }

}
