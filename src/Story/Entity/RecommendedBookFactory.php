<?php
namespace Story\Entity;

use Story\DB\EntityManagerProvider;
use Story\Model\Book;

class RecommendedBookFactory
{
    /**
     * @param $id
     * @return \Story\Entity\RecommendedBook
     */
    public static function get($id)
    {
        $em = EntityManagerProvider::getEntityManager();
        $rb = $em->find('Story\Entity\RecommendedBook', $id);
        if ($rb) {
            $rb->cover_url = Book::getCoverUrl($rb->store_id);
            $rb->ridibooks_sale_url = Book::getRidibooksSaleUrl($rb->store_id);
        }
        return $rb;
    }

    /**
     * @param $b_id
     * @param bool $show_all
     * @return \Story\Entity\RecommendedBook[]
     */
    public static function getRecommendedBookListByBid($b_id, $show_all = false)
    {
        $em = EntityManagerProvider::getEntityManager();
        $recommended_books = $em->getRepository('Story\Entity\RecommendedBook')
            ->findBy(array('b_id' => $b_id), array('seq' => 'ASC'));

        foreach ($recommended_books as $key => &$rb) {
            if ($rb) {
                $rb->cover_url = Book::getCoverUrl($rb->store_id);
                $rb->ridibooks_sale_url = Book::getRidibooksSaleUrl($rb->store_id);

                if (($rb->store_id == '' || $rb->title == '') && !$show_all) {
                    unset($recommended_books[$key]);
                }
            }
        }

        return $recommended_books;
    }

    public static function create($b_id)
    {
        $last_seq = self::getLastSeq($b_id);

        $rb = new RecommendedBook($b_id, $last_seq + 1);
        $em = EntityManagerProvider::getEntityManager();
        $em->persist($rb);
        $em->flush();
        return $rb->id;
    }

    private static function getLastSeq($b_id)
    {
        $sql = <<<EOT
select seq from recommended_book where b_id = ? order by seq desc limit 1
EOT;
        global $app;
        return $app['db']->fetchColumn($sql, array($b_id));
    }

    public static function hasRecommendedBooks($b_id)
    {
        $sql = <<<EOT
select count(*) from recommended_book where b_id = ? and store_id != '' and title != ''
EOT;
        global $app;
        return $app['db']->fetchColumn($sql, array($b_id));
    }

    public static function update($id, $values)
    {
        global $app;
        return $app['db']->update('recommended_book', $values, array('id' => $id));
    }

    public static function delete($id)
    {
        global $app;
        return $app['db']->delete('recommended_book', array('id' => $id));
    }

    public static function deleteByBid($b_id)
    {
        global $app;
        return $app['db']->delete('recommended_book', array('b_id' => $b_id));
    }
}