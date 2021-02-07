#!/bin/sh

echo "Add submodule from .gitmodule after cloning a repo."
echo ""
echo "You have to run this script at the root directory of your git repository."

# see <https://stackoverflow.com/questions/11258737/restore-git-submodules-from-gitmodules>

# add from .submodules to .git/confit
git submodule init

# fetch and update submodules to state of supermodule
git submodule update

# short
# git submodule --init update