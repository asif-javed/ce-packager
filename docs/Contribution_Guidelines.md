The source code for the Kaltura media delivery stack is spread across
many repositories on GitHub that mirror specific directories in Kaltura
R&D's canonical SVN repository.  


# [CE Packager](https://github.com/kaltura/ce-packager) Rebase Workflow

The CE Packager repository is manually synced from the R&D SVN (blue
dots are SVN revisions) into `*-mirror` branches in the GitHub repository.  Additionally,
`ce-packager` includes many modifications to to the packager to remove
reliance on SVN within the packaging scripts and instead use GitHub
repositories (GitHub commits are shown in green).

The first diagram shows the repository in a state where there have been
upstream updates to the Kaltura R&D SVN and the second diagram shows
what the repository looks like after we `git rebase` our changesets onto
the tail end of the recent commits from R&D.

Notice also that there is a commit on the GitHub repository marked in
red that can be applied to the R&D SVN (the green commits are git
specific modifications that do not need to be committed back to SVN).

`git-rebase` allows us to rewrite the history of the repository so that
the red commit may be inserted at the beginning of our GitHub history.
To shuffle the commits, find out the git hash of the last SVN revision
(this should be done after the rebase step mentioned earlier).

Once you have determined the commits you want to reorder, use `git
rebase -i <oldest commit id>` and reorder the lines of the file to move
your commit to the head of the queue.  Once the red commit is directly
following the last SVN commit, you may request that the commit be
committed back to the R&D SVN via `git svn dcommit` from the
syncronization server.  **Note that this is a manual task.**

![ce-packager rebase workflow](https://raw.github.com/kaltura/ce-packager/falcon/docs/images/CE%20GitHub%20Structure.png "Rebase Workflow")

**The rebase workflow requires that all modifications should be done on
working branches because once committed to a release branch like
`master` or `falcon` every developer must `git pull --force` to update
their local copies of the GitHub repository.**
