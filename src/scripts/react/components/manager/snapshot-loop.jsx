import React, { useEffect, useState } from 'react';

const SnapshotLoopItem = ({ snapshots, onSnapshotSelect }) => {
	const [posts, setPosts] = useState([]);
	const [error, setError] = useState(null);

	/**
	 * Set snapshots data
	 */
	useEffect(() => {
		if ('posts' in snapshots) {
			setPosts(snapshots.posts);
		}
	}, [snapshots]);

	if (error) {
		return (
			<tbody>
				<tr>
					<td colSpan='6'>Error: {error.message}</td>
				</tr>
			</tbody>
		);
	}
	if (posts.length === 0) {
		return (
			<tbody>
				<tr>
					<td colSpan='6'>No items found</td>
				</tr>
			</tbody>
		);
	}

	const viewSnapshot = (event) => {
		const snapshotId = parseInt(
			event.target.getAttribute('data-snapshot-id')
		);
		const snapshot = posts.find((snapshot) => snapshot.id === snapshotId);
		onSnapshotSelect(snapshot);
	};

	return (
		<tbody>
			{posts.map((item, index) => {
				if (null === item.post) return null;

				return (
					<tr key={index}>
						<td>{item.post.post_modified}</td>
						<td>{item.post.post_title}</td>
						<td>
							<a
								href={item.post.permalink}
								className='item-permalink'
							>
								{item.post.permalink}
							</a>
						</td>
						<td>{item.post.new_status}</td>
						<td>{item.user.display_name}</td>
						<td>
							<button
								className='button button-primary'
								data-snapshot-id={item.id}
								onClick={viewSnapshot}
							>
								View
							</button>
						</td>
					</tr>
				);
			})}
		</tbody>
	);
};

export default SnapshotLoopItem;
