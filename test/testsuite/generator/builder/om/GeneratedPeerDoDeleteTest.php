<?php
/*
 *  $Id: GeneratedPeerTest.php 1347 2009-12-03 21:06:36Z francois $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'tools/helpers/bookstore/BookstoreEmptyTestBase.php';

/**
 * Tests the delete methods of the generated Peer classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * peer operations.
 *
 * The database is relaoded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see        BookstoreDataPopulator
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    generator.builder.om
 */
class GeneratedPeerDoDeleteTest extends BookstoreEmptyTestBase
{
	protected function setUp()
	{
		parent::setUp();
		BookstoreDataPopulator::populate();
	}
		
	/**
	 * Test ability to delete multiple rows via single Criteria object.
	 */
	public function testDoDelete_MultiTable() {

		$selc = new Criteria();
		$selc->add(BookPeer::TITLE, "Harry Potter and the Order of the Phoenix");
		$hp = BookPeer::doSelectOne($selc);

		// print "Attempting to delete [multi-table] by found pk: ";
		$c = new Criteria();
		$c->add(BookPeer::ID, $hp->getId());
		// The only way for multi-delete to work currently
		// is to specify the author_id and publisher_id (i.e. the fkeys
		// have to be in the criteria).
		$c->add(AuthorPeer::ID, $hp->getAuthorId());
		$c->add(PublisherPeer::ID, $hp->getPublisherId());
		$c->setSingleRecord(true);
		BookPeer::doDelete($c);

		//print_r(AuthorPeer::doSelect(new Criteria()));

		// check to make sure the right # of records was removed
		$this->assertEquals(3, count(AuthorPeer::doSelect(new Criteria())), "Expected 3 authors after deleting.");
		$this->assertEquals(3, count(PublisherPeer::doSelect(new Criteria())), "Expected 3 publishers after deleting.");
		$this->assertEquals(3, count(BookPeer::doSelect(new Criteria())), "Expected 3 books after deleting.");
	}

	/**
	 * Test using a complex criteria to delete multiple rows from a single table.
	 */
	public function testDoDelete_ComplexCriteria() {

		//print "Attempting to delete books by complex criteria: ";
		$c = new Criteria();
		$cn = $c->getNewCriterion(BookPeer::ISBN, "043935806X");
		$cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0380977427"));
		$cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0140422161"));
		$c->add($cn);
		BookPeer::doDelete($c);

		// now there should only be one book left; "The Tin Drum"

		$books = BookPeer::doSelect(new Criteria());

		$this->assertEquals(1, count($books), "Expected 1 book remaining after deleting.");
		$this->assertEquals("The Tin Drum", $books[0]->getTitle(), "Expect the only remaining book to be 'The Tin Drum'");
	}

	/**
	 * Test that cascading deletes are happening correctly (whether emulated or native).
	 */
	public function testDoDelete_Cascade_Simple()
	{

		// The 'media' table will cascade from book deletes

		// 1) Assert the row exists right now

		$medias = MediaPeer::doSelect(new Criteria());
		$this->assertTrue(count($medias) > 0, "Expected to find at least one row in 'media' table.");
		$media = $medias[0];
		$mediaId = $media->getId();

		// 2) Delete the owning book

		$owningBookId = $media->getBookId();
		BookPeer::doDelete($owningBookId);

		// 3) Assert that the media row is now also gone

		$obj = MediaPeer::retrieveByPK($mediaId);
		$this->assertNull($obj, "Expect NULL when retrieving on no matching Media.");

	}

	/**
	 * Test that cascading deletes are happening correctly for composite pk.
	 * @link       http://propel.phpdb.org/trac/ticket/544
	 */
	public function testDoDelete_Cascade_CompositePK()
	{

		$origBceCount = BookstoreContestEntryPeer::doCount(new Criteria());

		$cust1 = new Customer();
		$cust1->setName("Cust1");
		$cust1->save();

		$cust2 = new Customer();
		$cust2->setName("Cust2");
		$cust2->save();

		$c1 = new Contest();
		$c1->setName("Contest1");
		$c1->save();

		$c2 = new Contest();
		$c2->setName("Contest2");
		$c2->save();

		$store1 = new Bookstore();
		$store1->setStoreName("Store1");
		$store1->save();

		$bc1 = new BookstoreContest();
		$bc1->setBookstore($store1);
		$bc1->setContest($c1);
		$bc1->save();

		$bc2 = new BookstoreContest();
		$bc2->setBookstore($store1);
		$bc2->setContest($c2);
		$bc2->save();

		$bce1 = new BookstoreContestEntry();
		$bce1->setEntryDate("now");
		$bce1->setCustomer($cust1);
		$bce1->setBookstoreContest($bc1);
		$bce1->save();

		$bce2 = new BookstoreContestEntry();
		$bce2->setEntryDate("now");
		$bce2->setCustomer($cust1);
		$bce2->setBookstoreContest($bc2);
		$bce2->save();

		// Now, if we remove $bc1, we expect *only* bce1 to be no longer valid.

		BookstoreContestPeer::doDelete($bc1);

		$newCount = BookstoreContestEntryPeer::doCount(new Criteria());

		$this->assertEquals($origBceCount + 1, $newCount, "Expected new number of rows in BCE to be orig + 1");

		$bcetest = BookstoreContestEntryPeer::retrieveByPK($store1->getId(), $c1->getId(), $cust1->getId());
		$this->assertNull($bcetest, "Expected BCE for store1 to be cascade deleted.");

		$bcetest2 = BookstoreContestEntryPeer::retrieveByPK($store1->getId(), $c2->getId(), $cust1->getId());
		$this->assertNotNull($bcetest2, "Expected BCE for store2 to NOT be cascade deleted.");

	}

	/**
	 * Test that onDelete="SETNULL" is happening correctly (whether emulated or native).
	 */
	public function testDoDelete_SetNull() {

		// The 'author_id' column in 'book' table will be set to null when author is deleted.

		// 1) Get an arbitrary book
		$c = new Criteria();
		$book = BookPeer::doSelectOne($c);
		$bookId = $book->getId();
		$authorId = $book->getAuthorId();
		unset($book);

		// 2) Delete the author for that book
		AuthorPeer::doDelete($authorId);

		// 3) Assert that the book.author_id column is now NULL

		$book = BookPeer::retrieveByPK($bookId);
		$this->assertNull($book->getAuthorId(), "Expect the book.author_id to be NULL after the author was removed.");

	}

	/**
	 * Test deleting a row by passing in the primary key to the doDelete() method.
	 */
	public function testDoDelete_ByPK() {

		// 1) get an arbitrary book
		$book = BookPeer::doSelectOne(new Criteria());
		$bookId = $book->getId();

		// 2) now delete that book
		BookPeer::doDelete($bookId);

		// 3) now make sure it's gone
		$obj = BookPeer::retrieveByPK($bookId);
		$this->assertNull($obj, "Expect NULL when retrieving on no matching Book.");

	}

	/**
	 * Test deleting a row by passing the generated object to doDelete().
	 */
	public function testDoDelete_ByObj() {

		// 1) get an arbitrary book
		$book = BookPeer::doSelectOne(new Criteria());
		$bookId = $book->getId();

		// 2) now delete that book
		BookPeer::doDelete($book);

		// 3) now make sure it's gone
		$obj = BookPeer::retrieveByPK($bookId);
		$this->assertNull($obj, "Expect NULL when retrieving on no matching Book.");

	}


	/**
	 * Test the doDeleteAll() method for single table.
	 */
	public function testDoDeleteAll() {

		BookPeer::doDeleteAll();
		$this->assertEquals(0, count(BookPeer::doSelect(new Criteria())), "Expect all book rows to have been deleted.");
	}

	/**
	 * Test the state of the instance pool after a doDeleteAll() call.
	 */
	public function testDoDeleteAllInstancePool()
	{
		$review = ReviewPeer::doSelectOne(new Criteria);
		$book = $review->getBook();
		BookPeer::doDeleteAll();
		$this->assertNull(BookPeer::retrieveByPk($book->getId()), 'doDeleteAll invalidates instance pool');
		$this->assertNull(ReviewPeer::retrieveByPk($review->getId()), 'doDeleteAll invalidates instance pool of releted tables with ON DELETE CASCADE');
	}

	/**
	 * Test the doDeleteAll() method when onDelete="CASCADE".
	 */
	public function testDoDeleteAll_Cascade() {

		BookPeer::doDeleteAll();
		$this->assertEquals(0, count(MediaPeer::doSelect(new Criteria())), "Expect all media rows to have been cascade deleted.");
		$this->assertEquals(0, count(ReviewPeer::doSelect(new Criteria())), "Expect all review rows to have been cascade deleted.");
	}

	/**
	 * Test the doDeleteAll() method when onDelete="SETNULL".
	 */
	public function testDoDeleteAll_SetNull() {

		$c = new Criteria();
		$c->add(BookPeer::AUTHOR_ID, null, Criteria::NOT_EQUAL);

		// 1) make sure there are some books with valid authors
		$this->assertTrue(count(BookPeer::doSelect($c)) > 0, "Expect some book.author_id columns that are not NULL.");

		// 2) delete all the authors
		AuthorPeer::doDeleteAll();

		// 3) now verify that the book.author_id columns are all nul
		$this->assertEquals(0, count(BookPeer::doSelect($c)), "Expect all book.author_id columns to be NULL.");
	}

	/**
	 * @link       http://propel.phpdb.org/trac/ticket/519
	 */
	public function testDoDeleteCompositePK()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);

		ReaderFavoritePeer::doDeleteAll();
		// Create book and reader with ID 1
		// Create book and reader with ID 2

		$this->createBookWithId(1);
		$this->createBookWithId(2);
		$this->createReaderWithId(1);
		$this->createReaderWithId(2);

		for ($i=1; $i <= 2; $i++) {
			for ($j=1; $j <= 2; $j++) {
				$bo = new BookOpinion();
				$bo->setBookId($i);
				$bo->setReaderId($j);
				$bo->save();
				
				$rf = new ReaderFavorite();
				$rf->setBookId($i);
				$rf->setReaderId($j);
				$rf->save();
			}
		}

		$this->assertEquals(4, ReaderFavoritePeer::doCount(new Criteria()));

		// Now delete 2 of those rows
		ReaderFavoritePeer::doDelete(array(array(1,1), array(2,2)));

		$this->assertEquals(2, ReaderFavoritePeer::doCount(new Criteria()));

		$this->assertNotNull(ReaderFavoritePeer::retrieveByPK(2,1));
		$this->assertNotNull(ReaderFavoritePeer::retrieveByPK(1,2));
		$this->assertNull(ReaderFavoritePeer::retrieveByPK(1,1));
		$this->assertNull(ReaderFavoritePeer::retrieveByPK(2,2));
	}
	
	/**
	 * Test the doInsert() method when passed a Criteria object.
	 */
	public function testDoInsert_Criteria() {

		$name = "A Sample Publisher - " . time();

		$values = new Criteria();
		$values->add(PublisherPeer::NAME, $name);
		PublisherPeer::doInsert($values);

		$c = new Criteria();
		$c->add(PublisherPeer::NAME, $name);

		$matches = PublisherPeer::doSelect($c);
		$this->assertEquals(1, count($matches), "Expect there to be exactly 1 publisher just-inserted.");
		$this->assertTrue( 1 != $matches[0]->getId(), "Expected to have different ID than one put in values Criteria.");

	}

	/**
	 * Test the doInsert() method when passed a generated object.
	 */
	public function testDoInsert_Obj() {

		$name = "A Sample Publisher - " . time();

		$values = new Publisher();
		$values->setName($name);
		PublisherPeer::doInsert($values);

		$c = new Criteria();
		$c->add(PublisherPeer::NAME, $name);

		$matches = PublisherPeer::doSelect($c);
		$this->assertEquals(1, count($matches), "Expect there to be exactly 1 publisher just-inserted.");
		$this->assertTrue( 1 != $matches[0]->getId(), "Expected to have different ID than one put in values Criteria.");

	}

	/**
	 * Tests the return type of doCount*() methods.
	 */
	public function testDoCountType()
	{
		$c = new Criteria();
		$this->assertType('integer', BookPeer::doCount($c), "Expected doCount() to return an integer.");
		$this->assertType('integer', BookPeer::doCountJoinAll($c), "Expected doCountJoinAll() to return an integer.");
		$this->assertType('integer', BookPeer::doCountJoinAuthor($c), "Expected doCountJoinAuthor() to return an integer.");
	}

	/**
	 * Tests the doCount() method with limit/offset.
	 */
	public function testDoCountLimitOffset()
	{
		BookPeer::doDeleteAll();

		for ($i=0; $i < 25; $i++) {
			$b = new Book();
			$b->setTitle("Book $i");
			$b->setISBN("ISBN $i");
			$b->save();
		}

		$c = new Criteria();
		$totalCount = BookPeer::doCount($c);

		$this->assertEquals(25, $totalCount);

		$c2 = new Criteria();
		$c2->setLimit(10);
		$this->assertEquals(10, BookPeer::doCount($c2));

		$c3 = new Criteria();
		$c3->setOffset(10);
		$this->assertEquals(15, BookPeer::doCount($c3));

		$c4 = new Criteria();
		$c4->setOffset(5);
		$c4->setLimit(5);
		$this->assertEquals(5, BookPeer::doCount($c4));

		$c5 = new Criteria();
		$c5->setOffset(20);
		$c5->setLimit(10);
		$this->assertEquals(5, BookPeer::doCount($c5));
	}

	/**
	 * Test doCountJoin*() methods.
	 */
	public function testDoCountJoin()
	{
		BookPeer::doDeleteAll();

		for ($i=0; $i < 25; $i++) {
			$b = new Book();
			$b->setTitle("Book $i");
			$b->setISBN("ISBN $i");
			$b->save();
		}

		$c = new Criteria();
		$totalCount = BookPeer::doCount($c);

		$this->assertEquals($totalCount, BookPeer::doCountJoinAuthor($c));
		$this->assertEquals($totalCount, BookPeer::doCountJoinPublisher($c));
	}
	
	/**
	 * Test doCountJoin*() methods with ORDER BY columns in Criteria.
	 * @link http://propel.phpdb.org/trac/ticket/627
	 */
	public function testDoCountJoinWithOrderBy()
	{
		$c = new Criteria(BookPeer::DATABASE_NAME);
		$c->addAscendingOrderByColumn(BookPeer::ID);
		
		// None of these should not throw an exception!
		BookPeer::doCountJoinAll($c); 
		BookPeer::doCountJoinAllExceptAuthor($c);
		BookPeer::doCountJoinAuthor($c);
	}
	
	/**
	 * Test passing null values to removeInstanceFromPool().
	 */
	public function testRemoveInstanceFromPool_Null()
	{
		// if it throws an exception, then it's broken.
		try {
			BookPeer::removeInstanceFromPool(null);
		} catch (Exception $x) {
			$this->fail("Expected to get no exception when removing an instance from the pool.");
		}
	}

	/**
	 * @see        testDoDeleteCompositePK()
	 */
	private function createBookWithId($id)
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$b = BookPeer::retrieveByPK($id);
		if (!$b) {
			$b = new Book();
			$b->setTitle("Book$id")->setISBN("BookISBN$id")->save();
			$b1Id = $b->getId();
			$sql = "UPDATE " . BookPeer::TABLE_NAME . " SET id = ? WHERE id = ?";
			$stmt = $con->prepare($sql);
			$stmt->bindValue(1, $id);
			$stmt->bindValue(2, $b1Id);
			$stmt->execute();
		}
	}

	/**
	 * @see        testDoDeleteCompositePK()
	 */
	private function createReaderWithId($id)
	{
		$con = Propel::getConnection(BookReaderPeer::DATABASE_NAME);
		$r = BookReaderPeer::retrieveByPK($id);
		if (!$r) {
			$r = new BookReader();
			$r->setName('Reader'.$id)->save();
			$r1Id = $r->getId();
			$sql = "UPDATE " . BookReaderPeer::TABLE_NAME . " SET id = ? WHERE id = ?";
			$stmt = $con->prepare($sql);
			$stmt->bindValue(1, $id);
			$stmt->bindValue(2, $r1Id);
			$stmt->execute();
		}
	}

}