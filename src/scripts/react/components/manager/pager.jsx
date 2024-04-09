import React, { useEffect, useState } from 'react';

const SnapshotPager = ({ args, snapshots, onPageSelect }) => {
	const [items, setItems] = useState({
		found: 0,
		pages: 1,
		posts: [],
	});
	const [current, setCurrent] = useState(1);
	/**
	 * Set snapshots data
	 */
	useEffect(() => {
		setItems(snapshots);
	}, [snapshots]);

	// Remove pager from dom
	if (snapshots.found === 0) {
		return;
	}

	const isDisabled = (page, dir) => {
		if ('last' === dir && page === items.pages) {
			return ' disabled';
		}

		if ('first' === dir && page === 1) {
			return ' disabled';
		}

		return '';
	};

	const goToPage = (event, anchorElement) => {
		// console.log(event, anchorElement);
		event.preventDefault();

		const pageNumber = parseInt(anchorElement.getAttribute('data-page'));
		setCurrent(pageNumber);
		onPageSelect(pageNumber);
	};

	return (
		<div className='tablenav bottom'>
			<div className='tablenav-pages'>
				<span className='displaying-num'>{items.found} items</span>

				<span className='pagination-links'>
					<a
						className={`first-page button${isDisabled(
							current,
							'first'
						)}`}
						href='#'
						data-page={1}
						onClick={(event) =>
							goToPage(event, event.currentTarget)
						}
					>
						<span className='screen-reader-text'>First page</span>
						<span aria-hidden='true'>«</span>
					</a>
					<a
						className={`prev-page button${isDisabled(
							current,
							'first'
						)}`}
						href='#'
						data-page={Math.max(1, current - 1)}
						onClick={(event) =>
							goToPage(event, event.currentTarget)
						}
					>
						<span className='screen-reader-text'>
							Previous page
						</span>
						<span aria-hidden='true'>‹</span>
					</a>
					<span className='screen-reader-text'>Current Page</span>
					<span
						id='table-paging'
						className='paging-input'
					>
						<span className='tablenav-paging-text'>
							{current} of
							<span className='total-pages'> {items.pages}</span>
						</span>
					</span>

					<a
						className={`next-page button${isDisabled(
							current,
							'last'
						)}`}
						href='#'
						data-page={Math.min(items.pages, current + 1)}
						onClick={(event) =>
							goToPage(event, event.currentTarget)
						}
					>
						<span className='screen-reader-text'>Next page</span>
						<span aria-hidden='true'>›</span>
					</a>

					<a
						className={`last-page button${isDisabled(
							current,
							'last'
						)}`}
						href='#'
						data-page={items.pages}
						onClick={(event) =>
							goToPage(event, event.currentTarget)
						}
					>
						<span className='screen-reader-text'>Last page</span>
						<span aria-hidden='true'>»</span>
					</a>
				</span>
			</div>
		</div>
	);
};

export default SnapshotPager;
